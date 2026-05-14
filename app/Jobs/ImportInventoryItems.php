<?php

namespace App\Jobs;

use App\Models\Inventory\Item;
use App\Models\Inventory\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportInventoryItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $categoryId;
    protected $itemType;
    protected $companyId;
    protected $userId;
    protected $batchId;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $categoryId, $itemType, $companyId, $userId, $batchId = null)
    {
        $this->filePath = $filePath;
        $this->categoryId = $categoryId;
        $this->itemType = $itemType;
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batch = null;

        try {
            Log::info('Starting import job', [
                'file' => $this->filePath,
                'batch_id' => $this->batchId
            ]);

            if ($this->batchId) {
                $batch = ImportBatch::find($this->batchId);
                if ($batch) {
                    $batch->markAsProcessing();
                    Log::info('Batch marked as processing', ['batch_id' => $batch->id]);
                }
            }

            if (!file_exists($this->filePath)) {
                throw new \Exception("CSV file not found: {$this->filePath}");
            }

            Log::info('Reading CSV file', ['file' => $this->filePath]);
            
            $csvData = array_map('str_getcsv', file($this->filePath));
            if (empty($csvData)) {
                throw new \Exception("CSV file is empty");
            }
            
            $header = array_shift($csvData);
            
            Log::info('CSV Header', ['columns' => $header]);

            // Validate CSV header
            $requiredColumns = ['name', 'code', 'unit_price'];
            $missingColumns = array_diff($requiredColumns, $header);
            
            if (!empty($missingColumns)) {
                throw new \Exception('Missing required columns: ' . implode(', ', $missingColumns));
            }

            $imported = 0;
            $errors = [];
            $batchSize = 100;
            $items = [];
            
            Log::info('Processing CSV rows', ['total_rows' => count($csvData)]);

            foreach ($csvData as $rowIndex => $row) {
                if (count($row) !== count($header)) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Column count mismatch";
                    continue;
                }

                $data = array_combine($header, $row);
                
                // Skip empty rows
                if (empty(trim($data['name'])) || empty(trim($data['code']))) {
                    continue;
                }

                try {
                    $hasWholesaleCol = array_key_exists('has_wholesale', $data);
                    $wholesalePriceCol = array_key_exists('wholesale_unit_price', $data);

                    $wantsWholesale = false;
                    if ($hasWholesaleCol) {
                        $rawFlag = strtolower(trim((string) ($data['has_wholesale'] ?? '')));
                        $wantsWholesale = $rawFlag !== '' && in_array($rawFlag, ['yes', 'true', '1', 'y'], true);
                    }

                    $wholesaleUnitPrice = null;
                    if ($wantsWholesale) {
                        $rawWs = $wholesalePriceCol ? trim((string) ($data['wholesale_unit_price'] ?? '')) : '';
                        if ($rawWs === '' || ! is_numeric($rawWs) || (float) $rawWs <= 0) {
                            throw new \Exception('wholesale_unit_price is required and must be greater than 0 when has_wholesale is Yes');
                        }
                        $wholesaleUnitPrice = (float) $rawWs;
                    }

                    $items[] = [
                        'company_id' => $this->companyId,
                        'category_id' => $this->categoryId,
                        'name' => trim($data['name']),
                        'code' => trim($data['code']),
                        'description' => isset($data['description']) ? trim($data['description']) : null,
                        'item_type' => $this->itemType,
                        'unit_of_measure' => isset($data['unit_of_measure']) ? trim($data['unit_of_measure']) : null,
                        'cost_price' => isset($data['cost_price']) && is_numeric($data['cost_price']) ? $data['cost_price'] : null,
                        'unit_price' => is_numeric($data['unit_price']) ? $data['unit_price'] : 0,
                        'has_wholesale' => $wantsWholesale,
                        'wholesale_unit_price' => $wholesaleUnitPrice,
                        'minimum_stock' => isset($data['minimum_stock']) && is_numeric($data['minimum_stock']) ? $data['minimum_stock'] : null,
                        'maximum_stock' => isset($data['maximum_stock']) && is_numeric($data['maximum_stock']) ? $data['maximum_stock'] : null,
                        'reorder_level' => isset($data['reorder_level']) && is_numeric($data['reorder_level']) ? $data['reorder_level'] : null,
                        'is_active' => true,
                        'track_stock' => $this->itemType === 'product',
                        'track_expiry' => isset($data['track_expiry']) ? (strtolower(trim($data['track_expiry'])) === 'yes' || strtolower(trim($data['track_expiry'])) === 'true' || strtolower(trim($data['track_expiry'])) === '1') : false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insert in batches
                    if (count($items) >= $batchSize) {
                        Log::info('Inserting batch', [
                            'count' => count($items),
                            'company_id' => $this->companyId,
                            'category_id' => $this->categoryId
                        ]);
                        Item::insert($items);
                        $imported += count($items);
                        $items = [];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing row ' . ($rowIndex + 2), [
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            // Insert remaining items
            if (!empty($items)) {
                Log::info('Inserting final batch', [
                    'count' => count($items)
                ]);
                Item::insert($items);
                $imported += count($items);
            }

            // Log the import completion
            $message = "Successfully imported {$imported} inventory items.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " errors occurred.";
                Log::warning($message, ['errors' => $errors]);
            } else {
                Log::info($message);
            }

            // Mark batch as completed
            if ($batch) {
                $batch->markAsCompleted($imported, count($errors), json_encode($errors));
                Log::info('Batch marked as completed', [
                    'batch_id' => $batch->id,
                    'imported' => $imported
                ]);
            }

            // Clean up the temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
                Log::info('Temporary file deleted');
            }

        } catch (\Exception $e) {
            Log::error('Inventory items import failed: ' . $e->getMessage(), [
                'file' => $this->filePath,
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'trace' => $e->getTraceAsString()
            ]);

            if ($batch) {
                $batch->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Inventory items import job failed after retries: ' . $exception->getMessage(), [
            'file' => $this->filePath,
            'company_id' => $this->companyId,
            'user_id' => $this->userId
        ]);

        if ($this->batchId) {
            $batch = ImportBatch::find($this->batchId);
            if ($batch) {
                $batch->markAsFailed($exception->getMessage());
            }
        }

        // Clean up the temporary file
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
