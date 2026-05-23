<?php

namespace App\Services\Purchase;

use App\Helpers\SmsHelper;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class DailyAccountsReportNotificationService
{
    public function __construct(
        private readonly DailyAccountsReportService $reportService
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function send(int $companyId, ?int $branchId, int $employeeId, string $entryDate, string $employeeName): array
    {
        $company = Company::query()->find($companyId);
        $phone = trim((string) ($company?->phone ?? ''));

        if ($phone === '') {
            return [
                'success' => false,
                'message' => 'Namba ya simu ya kampuni haijawekwa. Weka kwenye mipangilio ya kampuni.',
            ];
        }

        if (! SmsHelper::isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMS haijasanidiwa. Sanidi gateway ya SMS kwanza.',
            ];
        }

        $report = $this->reportService->build($companyId, $branchId, $employeeId, $entryDate);
        $smsBody = $this->reportService->buildNotificationMessage($employeeName, $report);

        $result = SmsHelper::send($phone, $smsBody);

        if (! ($result['success'] ?? false)) {
            Log::warning('Daily accounts report SMS failed.', [
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'entry_date' => $entryDate,
                'error' => $result['error'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'message' => 'Imeshindikana kutuma SMS: '.($result['error'] ?? 'hitilafu isiyojulikana'),
            ];
        }

        return [
            'success' => true,
            'message' => 'SMS imetumwa kwa namba ya kampuni.',
        ];
    }
}
