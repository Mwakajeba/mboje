<?php

namespace App\Services\Purchase;

use App\Helpers\SmsHelper;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyAccountsReportNotificationService
{
    /** @var array<string, string> */
    private const SECTION_LABELS = [
        'mauzo' => 'Mauzo/Mapato',
        'matumizi' => 'Matumizi',
        'manunuzi' => 'Manunuzi',
        'stoo' => 'Stoo',
        'all' => 'hesabu zote',
    ];

    public function __construct(
        private readonly DailyAccountsReportService $reportService
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function send(int $companyId, ?int $branchId, int $employeeId, string $entryDate, string $employeeName): array
    {
        $phone = $this->companyPhone($companyId);

        if ($phone === null) {
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

    /**
     * Short SMS when a report line/section is edited or deleted (best-effort; does not throw).
     *
     * @param  'updated'|'line_deleted'|'section_deleted'|'all_deleted'  $changeKind
     */
    public function sendChangeAlert(
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $entryDate,
        string $employeeName,
        string $changeKind,
        string $sectionType,
        ?string $actorName = null,
        ?float $kiasiBefore = null,
        ?float $kiasiAfter = null
    ): void {
        $phone = $this->companyPhone($companyId);
        if ($phone === null || ! SmsHelper::isConfigured()) {
            return;
        }

        $smsBody = $this->buildChangeMessage(
            $employeeName,
            $entryDate,
            $changeKind,
            $sectionType,
            $actorName,
            $kiasiBefore,
            $kiasiAfter
        );

        $result = SmsHelper::send($phone, $smsBody);

        if (! ($result['success'] ?? false)) {
            Log::warning('Daily accounts change SMS failed.', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'employee_id' => $employeeId,
                'entry_date' => $entryDate,
                'change_kind' => $changeKind,
                'section_type' => $sectionType,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }
    }

    private function companyPhone(int $companyId): ?string
    {
        $company = Company::query()->find($companyId);
        $phone = trim((string) ($company?->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }

    /**
     * @param  'updated'|'line_deleted'|'section_deleted'|'all_deleted'  $changeKind
     */
    private function buildChangeMessage(
        string $employeeName,
        string $entryDate,
        string $changeKind,
        string $sectionType,
        ?string $actorName,
        ?float $kiasiBefore = null,
        ?float $kiasiAfter = null
    ): string {
        $dateFormatted = Carbon::parse($entryDate)->format('d/m/Y');
        $sectionLabel = self::SECTION_LABELS[$sectionType] ?? ucfirst($sectionType);
        $byActor = $actorName ? ' na '.trim($actorName) : '';
        $amountSuffix = $this->amountSuffix($sectionType, $changeKind, $kiasiBefore, $kiasiAfter);

        $text = match ($changeKind) {
            'updated' => sprintf(
                'Hesabu: %s ya %s tarehe %s imebadilishwa%s%s.',
                $sectionLabel,
                $employeeName,
                $dateFormatted,
                $byActor,
                $amountSuffix
            ),
            'line_deleted' => sprintf(
                'Hesabu: rekodi ya %s ya %s tarehe %s imefutwa%s%s.',
                $sectionLabel,
                $employeeName,
                $dateFormatted,
                $byActor,
                $amountSuffix
            ),
            'section_deleted' => sprintf(
                'Hesabu: sehemu ya %s ya %s tarehe %s imefutwa%s%s.',
                $sectionLabel,
                $employeeName,
                $dateFormatted,
                $byActor,
                $amountSuffix
            ),
            'all_deleted' => sprintf(
                'Hesabu: rekodi zote za %s tarehe %s zimefutwa%s.',
                $employeeName,
                $dateFormatted,
                $byActor
            ),
            default => sprintf(
                'Hesabu: mabadiliko kwenye rekodi za %s tarehe %s%s%s.',
                $employeeName,
                $dateFormatted,
                $byActor,
                $amountSuffix
            ),
        };

        return $text.' mboje.sys.co.tz';
    }

    private function amountSuffix(
        string $sectionType,
        string $changeKind,
        ?float $kiasiBefore,
        ?float $kiasiAfter
    ): string {
        if ($sectionType === 'stoo' || $sectionType === 'all') {
            return '';
        }

        if ($kiasiBefore === null) {
            return '';
        }

        $fmt = fn (float $n) => number_format($n, 2, '.', ',');

        if ($changeKind === 'updated' && $kiasiAfter !== null) {
            return sprintf(' Kiasi kilichokuwa %s, sasa ni %s', $fmt($kiasiBefore), $fmt($kiasiAfter));
        }

        if (in_array($changeKind, ['line_deleted', 'section_deleted'], true)) {
            return ' Kiasi kilichofutwa '.$fmt($kiasiBefore);
        }

        return '';
    }
}
