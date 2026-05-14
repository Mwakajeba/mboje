<?php

namespace App\Jobs;

use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPurchaseInvoiceItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceId;
    protected $filePath;
    protected $discountAmount;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($invoiceId, $filePath, $discountAmount = 0)
    {
        $this->invoiceId = $invoiceId;
        $this->filePath = $filePath;
        $this->discountAmount = $discountAmount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ImportPurchaseInvoiceItemsJob: Starting', [
            'invoice_id' => $this->invoiceId,
            'file' => $this->filePath
        ]);

        DB::beginTransaction();
        try {
            $invoice = PurchaseInvoice::find($this->invoiceId);
            if (!$invoice) {
                throw new \Exception("Purchase invoice not found: {$this->invoiceId}");
            }

            if (!file_exists($this->filePath)) {
                throw new \Exception("CSV file not found: {$this->filePath}");
            }

            Log::info('ImportPurchaseInvoiceItemsJob: Reading CSV file', ['file' => $this->filePath]);
            
            // Read CSV file with proper handling of quoted fields and newlines (same as ImportInventoryItems)
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
            
            Log::info('ImportPurchaseInvoiceItemsJob: CSV Header', ['columns' => $header]);

            // Normalize header to lowercase for case-insensitive matching
            $headerLower = array_map('strtolower', $header);
            $headerMap = array_combine($headerLower, $header); // Map lowercase to original case
            
            // Expected CSV columns: Item Type (optional, defaults to 'inventory'), Item ID (or Inventory Item ID + Asset ID), Item Name, Quantity, Unit Cost, VAT Type, VAT Rate, Notes, Expiry Date, Batch Number, Description
            // Support both formats: single "Item ID" column OR separate "Inventory Item ID" and "Asset ID" columns
            // Make validation case-insensitive
            $requiredColumns = [
                'item name' => 'Item Name',
                'quantity' => 'Quantity', 
                'unit cost' => 'Unit Cost', 
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
            
            // Check if we have Item ID or Inventory Item ID - case-insensitive
            $hasItemId = in_array('item id', $headerLower);
            $hasInventoryItemId = in_array('inventory item id', $headerLower);
            
            if (!$hasItemId && !$hasInventoryItemId) {
                // Try to be flexible - Item ID might be optional for inventory items
                Log::warning('ImportPurchaseInvoiceItemsJob: No Item ID column found, will try to proceed without it');
            }

            $processedCount = 0;
            $errors = [];
            $subtotal = 0;
            $vatAmount = 0;
            $total = 0;
            
            Log::info('ImportPurchaseInvoiceItemsJob: Processing CSV rows', ['total_rows' => count($csvData)]);

            foreach ($csvData as $rowIndex => $row) {
                // Handle column count mismatches (same as ImportInventoryItems)
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
                
                // Skip empty rows (same as ImportInventoryItems)
                $itemName = trim($data['Item Name'] ?? $data['item name'] ?? '');
                if (empty($itemName)) {
                    continue;
                }

                try {
                    $itemType = strtolower(trim($data['Item Type'] ?? 'inventory'));
                    
                    // Handle both CSV formats: single "Item ID" or separate "Inventory Item ID" and "Asset ID"
                    // Use case-insensitive lookup
                    $itemId = '';
                    if (isset($data['Item ID']) || isset($data['item id'])) {
                        $itemId = trim($data['Item ID'] ?? $data['item id'] ?? '');
                    } elseif (isset($data['Inventory Item ID']) || isset($data['inventory item id'])) {
                        $itemId = trim($data['Inventory Item ID'] ?? $data['inventory item id'] ?? '');
                    }
                    
                    $quantity = (float) ($data['Quantity'] ?? $data['quantity'] ?? 0);
                    $unitCost = (float) ($data['Unit Cost'] ?? $data['unit cost'] ?? 0);
                    $vatType = strtolower(trim($data['VAT Type'] ?? $data['vat type'] ?? 'no_vat'));
                    $vatRate = (float) ($data['VAT Rate'] ?? $data['vat rate'] ?? 0);
                    $notes = trim($data['Notes'] ?? $data['notes'] ?? '');
                    $expiryDateValue = $data['Expiry Date'] ?? $data['expiry date'] ?? '';
                    $expiryDate = !empty($expiryDateValue) ? \Carbon\Carbon::parse($expiryDateValue) : null;
                    $batchNumber = trim($data['Batch Number'] ?? $data['batch number'] ?? '');
                    $description = trim($data['Description'] ?? $data['description'] ?? '');

                    if ($quantity <= 0 || $unitCost <= 0) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Quantity and unit cost must be greater than 0";
                        continue;
                    }

                    // Calculate VAT and line total
                    $base = $quantity * $unitCost;
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

                    $inventoryItemId = null;
                    if (!empty($itemId)) {
                        $inventoryItemId = (int) $itemId;
                    }

                    // Only inventory items supported (asset module removed)
                    $itemType = 'inventory';

                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'item_type' => $itemType,
                        'inventory_item_id' => $inventoryItemId,
                        'description' => $description ?: null,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'vat_type' => $vatType,
                        'vat_rate' => $vatRate,
                        'vat_amount' => $vat,
                        'line_total' => $lineTotal,
                        'expiry_date' => $itemType === 'inventory' ? $expiryDate : null,
                        'batch_number' => $itemType === 'inventory' && !empty($batchNumber) ? $batchNumber : null,
                    ]);

                    $subtotal += ($vatType === 'inclusive') ? ($base - $vat) : $base;
                    $vatAmount += $vat;
                    $total += $lineTotal;
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error('ImportPurchaseInvoiceItemsJob: Error processing row ' . ($rowIndex + 2), [
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            // Update invoice totals
            $discountAmount = (float) ($this->discountAmount ?? 0);
            $invoice->update([
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'discount_amount' => $discountAmount,
                'withholding_tax_amount' => 0,
                'total_amount' => max(0, $subtotal + $vatAmount - $discountAmount),
                'status' => 'open',
            ]);

            // Post GL transactions and inventory movements
            $invoice->load('items');
            $invoice->postGlTransactions();
            $invoice->postInventoryMovements();

            Log::info('ImportPurchaseInvoiceItemsJob: Completed successfully', [
                'invoice_id' => $invoice->id,
                'processed_count' => $processedCount,
                'errors_count' => count($errors)
            ]);

            if (!empty($errors)) {
                Log::warning('ImportPurchaseInvoiceItemsJob: Errors occurred', ['errors' => $errors]);
            }

            DB::commit();

            // Clean up the temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
                Log::info('ImportPurchaseInvoiceItemsJob: Temporary file deleted');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ImportPurchaseInvoiceItemsJob: Failed', [
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                 'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Clean up the temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
            
            throw $e; // Let the job retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ImportPurchaseInvoiceItemsJob: Job failed permanently', [
            'invoice_id' => $this->invoiceId,
            'error' => $exception->getMessage()
        ]);

        // Clean up the temporary file
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        // Optionally update invoice status to indicate failure
        $invoice = PurchaseInvoice::find($this->invoiceId);
        if ($invoice) {
            // You could add a status field to track import failures
            // For now, we'll just log it
        }
    }
}
