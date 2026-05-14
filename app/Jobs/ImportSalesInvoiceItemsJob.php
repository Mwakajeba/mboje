<?php

namespace App\Jobs;

use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;
use App\Services\ExpiryStockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportSalesInvoiceItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceId;
    protected $filePath;
    protected $companyId;
    protected $branchId;
    protected $locationId;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $invoiceId, $companyId, $branchId, $locationId)
    {
        $this->filePath = $filePath;
        $this->invoiceId = $invoiceId;
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        $this->locationId = $locationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ImportSalesInvoiceItemsJob: Starting', [
            'invoice_id' => $this->invoiceId,
            'file' => $this->filePath
        ]);

        DB::beginTransaction();
        try {
            $invoice = SalesInvoice::find($this->invoiceId);
            if (!$invoice) {
                throw new \Exception("Sales invoice not found: {$this->invoiceId}");
            }

            if (!$this->locationId) {
                throw new \Exception("Location ID is required to create inventory movements for sales invoice {$this->invoiceId}");
            }

            if (!file_exists($this->filePath)) {
                throw new \Exception("CSV file not found: {$this->filePath}");
            }

            Log::info('ImportSalesInvoiceItemsJob: Reading CSV file', ['file' => $this->filePath]);
            
            // Read CSV file with proper handling of quoted fields and newlines
            $csvData = [];
            if (($handle = fopen($this->filePath, 'r')) !== false) {
                while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                    $csvData[] = $row;
                }
                fclose($handle);
            } else {
                throw new \Exception("Could not open CSV file: {$this->filePath}");
            }
            
            if (empty($csvData)) {
                throw new \Exception("CSV file is empty");
            }
            
            $header = array_shift($csvData);
            $header = array_map('trim', array_map(function($h) {
                return str_replace('"', '', $h);
            }, $header));
            
            Log::info('ImportSalesInvoiceItemsJob: CSV Header', ['columns' => $header]);

            // Normalize header to lowercase for case-insensitive matching
            $headerLower = array_map('strtolower', $header);
            $headerMap = array_combine($headerLower, $header); // Map lowercase to original case
            
            // Expected CSV columns: Inventory Item ID, Item Name, Quantity, Unit Price, VAT Type, VAT Rate, Discount Type, Discount Rate, Notes
            $requiredColumns = [
                'item name' => 'Item Name',
                'quantity' => 'Quantity', 
                'unit price' => 'Unit Price', 
                'vat type' => 'VAT Type', 
                'vat rate' => 'VAT Rate'
            ];
            
            $missingColumns = [];
            foreach ($requiredColumns as $lowerKey => $originalName) {
                if (!in_array($lowerKey, $headerLower)) {
                    $missingColumns[] = $originalName;
                }
            }
            
            if (!empty($missingColumns)) {
                $foundColumns = implode(', ', $header);
                $errorMsg = 'Missing required columns: ' . implode(', ', $missingColumns);
                $errorMsg .= '. Found columns: ' . $foundColumns;
                throw new \Exception($errorMsg);
            }
            
            // Check if we have Inventory Item ID column - case-insensitive
            $hasInventoryItemId = in_array('inventory item id', $headerLower);
            
            if (!$hasInventoryItemId) {
                Log::warning('ImportSalesInvoiceItemsJob: No Inventory Item ID column found, will try to proceed without it');
            }

            $processedCount = 0;
            $errors = [];
            $subtotal = 0;
            $vatAmount = 0;
            $total = 0;
            
            Log::info('ImportSalesInvoiceItemsJob: Processing CSV rows', ['total_rows' => count($csvData)]);

            foreach ($csvData as $rowIndex => $row) {
                // Handle column count mismatches
                if (count($row) !== count($header)) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Column count mismatch";
                    continue;
                }

                // Create data array with case-insensitive lookup
                $data = [];
                foreach ($header as $index => $colName) {
                    $data[$colName] = $row[$index] ?? '';
                    // Also add lowercase key for case-insensitive access
                    $data[strtolower($colName)] = $row[$index] ?? '';
                }
                
                // Skip empty rows
                $itemName = trim($data['Item Name'] ?? $data['item name'] ?? '');
                if (empty($itemName)) {
                    continue;
                }

                try {
                    // Use case-insensitive lookup
                    $inventoryItemId = null;
                    if (isset($data['Inventory Item ID']) || isset($data['inventory item id'])) {
                        $itemIdValue = trim($data['Inventory Item ID'] ?? $data['inventory item id'] ?? '');
                        if (!empty($itemIdValue)) {
                            $inventoryItemId = (int) $itemIdValue;
                            // Verify inventory item exists
                            $inventoryItem = InventoryItem::find($inventoryItemId);
                            if (!$inventoryItem || $inventoryItem->company_id != $this->companyId) {
                                $errors[] = "Row " . ($rowIndex + 2) . ": Inventory item not found or does not belong to company";
                                continue;
                            }
                        }
                    }
                    
                    $quantity = (float) ($data['Quantity'] ?? $data['quantity'] ?? 0);
                    $unitPrice = (float) ($data['Unit Price'] ?? $data['unit price'] ?? 0);
                    $vatType = strtolower(trim($data['VAT Type'] ?? $data['vat type'] ?? 'no_vat'));
                    $vatRate = (float) ($data['VAT Rate'] ?? $data['vat rate'] ?? 0);
                    $discountType = strtolower(trim($data['Discount Type'] ?? $data['discount type'] ?? ''));
                    $discountRate = (float) ($data['Discount Rate'] ?? $data['discount rate'] ?? 0);
                    $notes = trim($data['Notes'] ?? $data['notes'] ?? '');

                    if ($quantity <= 0 || $unitPrice <= 0) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Quantity and unit price must be greater than 0";
                        continue;
                    }

                    // Calculate discount
                    $discountAmount = 0;
                    if (!empty($discountType) && $discountRate > 0) {
                        $baseAmount = $quantity * $unitPrice;
                        if ($discountType === 'percentage') {
                            $discountAmount = $baseAmount * ($discountRate / 100);
                        } else {
                            $discountAmount = $discountRate;
                        }
                    }

                    // Calculate VAT and line total
                    $base = ($quantity * $unitPrice) - $discountAmount;
                    $vat = 0;
                    $lineTotal = 0;

                    if ($vatType === 'inclusive' && $vatRate > 0) {
                        $vat = $base * ($vatRate / (100 + $vatRate));
                        $lineTotal = $base;
                    } elseif ($vatType === 'exclusive' && $vatRate > 0) {
                        $vat = $base * ($vatRate / 100);
                        $lineTotal = $base + $vat;
                    } else {
                        $lineTotal = $base;
                    }

                    // Get item code and model from inventory item if available
                    $itemCode = '';
                    $inventoryItem = null;
                    if ($inventoryItemId) {
                        $inventoryItem = InventoryItem::find($inventoryItemId);
                        $itemCode = $inventoryItem ? $inventoryItem->code : '';
                    }

                    /** @var SalesInvoiceItem $invoiceItem */
                    $invoiceItem = SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'inventory_item_id' => $inventoryItemId,
                        'item_name' => $itemName,
                        'item_code' => $itemCode,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'vat_type' => $vatType,
                        'vat_rate' => $vatRate,
                        'vat_amount' => $vat,
                        'discount_type' => !empty($discountType) ? $discountType : null,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $discountAmount,
                        'line_total' => $lineTotal,
                        'notes' => !empty($notes) ? $notes : null,
                    ]);

                    $subtotal += ($vatType === 'inclusive') ? ($base - $vat) : $base;
                    $vatAmount += $vat;
                    $total += $lineTotal;
                    $processedCount++;

                    // Create inventory movement for stock out (only for inventory products that track stock)
                    if ($inventoryItem && $inventoryItem->track_stock && $inventoryItem->item_type === 'product') {
                        $stockService = new InventoryStockService();
                        $costService = new InventoryCostService();

                        $balanceBefore = $stockService->getItemStockAtLocation($inventoryItem->id, $this->locationId);
                        $balanceAfter = $balanceBefore - $quantity;

                        // Pass branch/location for fallback cost resolution (location → branch → default)
                        $costInfo = $costService->removeInventory(
                            $inventoryItem->id,
                            $quantity,
                            'sale',
                            'Sales Invoice: ' . $invoice->invoice_number,
                            $invoice->invoice_date,
                            $this->branchId ?? $invoice->branch_id,
                            $this->locationId
                        );

                        InventoryMovement::create([
                            'item_id' => $inventoryItem->id,
                            'user_id' => $invoice->created_by ?? null,
                            'branch_id' => $this->branchId ?? $invoice->branch_id,
                            'location_id' => $this->locationId,
                            'movement_type' => 'sold',
                            'quantity' => $quantity,
                            'unit_price' => $costInfo['average_unit_cost'],
                            'unit_cost' => $costInfo['average_unit_cost'],
                            'total_cost' => $costInfo['total_cost'],
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'reference' => 'Sales Invoice: ' . $invoice->invoice_number,
                            'reference_type' => 'sales_invoice',
                            'reference_id' => $invoice->id,
                            'notes' => 'Stock sold via sales invoice import',
                            'movement_date' => $invoice->invoice_date,
                        ]);

                        // Consume stock using FEFO if item tracks expiry
                        $consumedLayers = [];
                        $earliestExpiryDate = null;
                        $batchNumbers = [];

                        if ($inventoryItem->track_expiry) {
                            $expiryService = new ExpiryStockService();
                            $consumedLayers = $expiryService->consumeStock(
                                $inventoryItem->id,
                                $this->locationId,
                                $quantity,
                                'FEFO'
                            );

                            if (!empty($consumedLayers)) {
                                $earliestExpiryDate = $consumedLayers[0]['expiry_date'];
                                $batchNumbers = array_column($consumedLayers, 'batch_number');
                            }

                            foreach ($consumedLayers as $layer) {
                                Log::info('Sales Invoice Import: Consumed stock with expiry', [
                                    'invoice_id' => $invoice->id,
                                    'item_id' => $inventoryItem->id,
                                    'batch_number' => $layer['batch_number'],
                                    'expiry_date' => $layer['expiry_date'],
                                    'quantity' => $layer['quantity'],
                                    'unit_cost' => $layer['unit_cost'],
                                ]);
                            }
                        }

                        // Update invoice item with expiry information
                        $invoiceItem->update([
                            'batch_number' => !empty($batchNumbers) ? implode(', ', $batchNumbers) : null,
                            'expiry_date' => $earliestExpiryDate,
                            'expiry_consumption_details' => $consumedLayers,
                        ]);
                    } else {
                        Log::info('Sales Invoice Import: Skipping movement creation for item', [
                            'invoice_id' => $invoice->id,
                            'item_id' => $inventoryItemId,
                            'item_name' => $itemName,
                            'reason' => $inventoryItem
                                ? (!$inventoryItem->track_stock
                                    ? 'Item does not track stock'
                                    : 'Item type is not product: ' . $inventoryItem->item_type)
                                : 'Inventory item not found or not linked',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('ImportSalesInvoiceItemsJob: Error processing row ' . ($rowIndex + 2), [
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            // Update invoice totals
            $invoice->update([
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total_amount' => $total,
                'balance_due' => $total - ($invoice->paid_amount ?? 0),
            ]);

            DB::commit();

            Log::info('ImportSalesInvoiceItemsJob: Completed', [
                'invoice_id' => $invoice->id,
                'processed' => $processedCount,
                'errors' => count($errors),
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total' => $total
            ]);

            if (!empty($errors)) {
                Log::warning('ImportSalesInvoiceItemsJob: Completed with errors', ['errors' => $errors]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ImportSalesInvoiceItemsJob: Failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
