<?php

namespace App\Imports;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetRevaluation;
use App\Models\Assets\RevaluationBatch;
use App\Services\Assets\RevaluationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetRevaluationImport implements ToCollection, WithHeadingRow
{
    protected array $validated;

    protected int $companyId;

    protected ?int $branchId;

    protected int $userId;

    protected ?string $valuationReportPath;

    protected array $attachments;

    public ?RevaluationBatch $batch = null;

    public array $createdRevaluations = [];

    public function __construct(
        array $validated,
        int $companyId,
        ?int $branchId,
        int $userId,
        ?string $valuationReportPath = null,
        array $attachments = []
    ) {
        $this->validated = $validated;
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        $this->userId = $userId;
        $this->valuationReportPath = $valuationReportPath;
        $this->attachments = $attachments;
    }

    public function collection(Collection $rows): void
    {
        $seenAssetCodes = [];

        DB::transaction(function () use ($rows, &$seenAssetCodes) {
            $assets = collect();
            $assetRows = [];

            foreach ($rows as $rowIndex => $row) {
                $assetCode = $this->normalizeValue($row['asset_code'] ?? null);
                $fairValue = $this->normalizeValue($row['fair_value'] ?? null);

                if (empty($assetCode) && (empty($fairValue) || $fairValue === 0)) {
                    continue;
                }

                if (empty($assetCode)) {
                    throw new \Exception("Row " . ($rowIndex + 2) . ": Asset code is required.");
                }

                if (empty($fairValue) && $fairValue !== 0 && $fairValue !== '0') {
                    throw new \Exception("Row " . ($rowIndex + 2) . " ({$assetCode}): Fair value is required.");
                }

                if (!is_numeric($fairValue) || (float) $fairValue < 0) {
                    throw new \Exception("Row " . ($rowIndex + 2) . " ({$assetCode}): Fair value must be a non-negative number.");
                }

                if (isset($seenAssetCodes[$assetCode])) {
                    throw new \Exception("Row " . ($rowIndex + 2) . ": Duplicate asset code '{$assetCode}' in the same file. Each asset may appear only once per import.");
                }
                $seenAssetCodes[$assetCode] = true;

                $asset = Asset::with('category')
                    ->where('code', $assetCode)
                    ->where('company_id', $this->companyId)
                    ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
                    ->first();

                if (!$asset) {
                    throw new \Exception("Row " . ($rowIndex + 2) . ": Asset with code '{$assetCode}' not found. Please ensure the asset exists and belongs to your company.");
                }

                $assetRows[] = [
                    'asset' => $asset,
                    'fair_value' => (float) $fairValue,
                    'useful_life_after' => $this->parseOptionalInt($row['useful_life_after'] ?? null),
                    'residual_value_after' => $this->parseOptionalNumeric($row['residual_value_after'] ?? null),
                ];
            }

            if (empty($assetRows)) {
                throw new \Exception("No valid data rows found. Please ensure the file contains at least one row with asset_code and fair_value.");
            }

            $batchNumber = 'BATCH-REV-' . date('Y') . '-' . str_pad(
                RevaluationBatch::whereYear('created_at', now()->year)->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $firstAsset = $assetRows[0]['asset'];
            $this->batch = RevaluationBatch::create([
                'company_id' => $this->companyId,
                'branch_id' => $this->branchId ?? $firstAsset->branch_id,
                'batch_number' => $batchNumber,
                'revaluation_date' => $this->validated['revaluation_date'],
                'valuation_model' => $this->validated['valuation_model'],
                'valuer_name' => $this->validated['valuer_name'] ?? null,
                'valuer_license' => $this->validated['valuer_license'] ?? null,
                'valuer_company' => $this->validated['valuer_company'] ?? null,
                'valuation_report_ref' => $this->validated['valuation_report_ref'] ?? null,
                'valuation_report_path' => $this->valuationReportPath,
                'reason' => $this->validated['reason'],
                'status' => 'draft',
                'attachments' => $this->attachments,
                'created_by' => $this->userId,
            ]);

            $baseRevaluationNumber = AssetRevaluation::whereYear('created_at', now()->year)->count();
            $revaluationService = app(RevaluationService::class);

            foreach ($assetRows as $index => $rowData) {
                $asset = $rowData['asset'];
                $fairValue = $rowData['fair_value'];
                $usefulLifeAfter = $rowData['useful_life_after'] ?? $this->validated['useful_life_after'] ?? null;
                $residualValueAfter = $rowData['residual_value_after'] ?? $this->validated['residual_value_after'] ?? null;

                $revaluationNumber = 'REV-' . date('Y') . '-' . str_pad(
                    $baseRevaluationNumber + $index + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

                $revaluation = AssetRevaluation::create([
                    'batch_id' => $this->batch->id,
                    'company_id' => $this->companyId,
                    'branch_id' => $this->branchId ?? $asset->branch_id,
                    'asset_id' => $asset->id,
                    'revaluation_number' => $revaluationNumber,
                    'revaluation_date' => $this->validated['revaluation_date'],
                    'valuation_model' => $this->validated['valuation_model'],
                    'valuer_name' => $this->validated['valuer_name'] ?? null,
                    'valuer_license' => $this->validated['valuer_license'] ?? null,
                    'valuer_company' => $this->validated['valuer_company'] ?? null,
                    'valuation_report_ref' => $this->validated['valuation_report_ref'] ?? null,
                    'valuation_report_path' => $this->valuationReportPath,
                    'reason' => $this->validated['reason'],
                    'fair_value' => $fairValue,
                    'useful_life_after' => $usefulLifeAfter,
                    'residual_value_after' => $residualValueAfter,
                    'revaluation_reserve_account_id' => $this->validated['revaluation_reserve_account_id'] ?? $asset->category->revaluation_reserve_account_id ?? null,
                    'status' => 'draft',
                    'attachments' => $this->attachments,
                    'created_by' => $this->userId,
                ]);

                $result = $revaluationService->processRevaluation($revaluation, [
                    'fair_value' => $fairValue,
                    'useful_life_after' => $usefulLifeAfter,
                    'residual_value_after' => $residualValueAfter,
                ]);

                if (!$result['success']) {
                    throw new \Exception("Row " . ($index + 2) . " ({$asset->code}): " . $result['message']);
                }

                $this->createdRevaluations[] = $revaluation;
            }
        });
    }

    protected function normalizeValue($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return $value;
        }
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    protected function parseOptionalInt($value): ?int
    {
        $v = $this->normalizeValue($value);
        if ($v === null || $v === '') {
            return null;
        }
        return is_numeric($v) ? (int) $v : null;
    }

    protected function parseOptionalNumeric($value): ?float
    {
        $v = $this->normalizeValue($value);
        if ($v === null || $v === '') {
            return null;
        }
        return is_numeric($v) ? (float) $v : null;
    }
}
