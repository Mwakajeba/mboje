<?php

namespace App\Services;

use App\Models\Provision;
use App\Models\Contingency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProvisionDisclosureService
{
    /**
     * Generate IAS 37 disclosure data for a reporting period
     *
     * @param int $companyId
     * @param string $periodStart Y-m-d format
     * @param string $periodEnd Y-m-d format
     * @return array
     */
    public function generateDisclosure(int $companyId, string $periodStart, string $periodEnd): array
    {
        $startDate = Carbon::parse($periodStart);
        $endDate = Carbon::parse($periodEnd);

        // Get all provisions for the company
        $provisions = Provision::forCompany($companyId)
            ->where(function ($query) use ($startDate, $endDate) {
                // Provisions that existed during the period
                $query->where('created_at', '<=', $endDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('deleted_at')
                            ->orWhere('deleted_at', '>', $startDate);
                    });
            })
            ->with(['expenseAccount', 'provisionAccount', 'branch'])
            ->get();

        // Group by provision type
        $byType = $provisions->groupBy('provision_type');

        // Calculate movements
        $movements = $this->calculateMovements($provisions, $startDate, $endDate);

        // Get contingencies
        $contingencies = Contingency::where('company_id', $companyId)
            ->where('created_at', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>', $startDate);
            })
            ->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'provisions_by_type' => $byType->map(function ($group) use ($startDate, $endDate) {
                return $this->calculateTypeDisclosure($group, $startDate, $endDate);
            }),
            'total_provisions' => $this->calculateTotalDisclosure($provisions, $startDate, $endDate),
            'contingencies' => $contingencies->map(function ($contingency) {
                return [
                    'nature' => $contingency->nature,
                    'type' => $contingency->contingency_type,
                    'probability' => $contingency->probability,
                    'estimated_effect' => $contingency->estimated_effect,
                    'uncertainties' => $contingency->uncertainties,
                ];
            }),
            'movements' => $movements,
        ];
    }

    /**
     * Calculate disclosure for a specific provision type
     */
    private function calculateTypeDisclosure($provisions, Carbon $startDate, Carbon $endDate): array
    {
        $openingBalance = 0;
        $additions = 0;
        $utilisations = 0;
        $reversals = 0;
        $unwinding = 0;
        $remeasurements = 0;
        $closingBalance = 0;

        foreach ($provisions as $provision) {
            // Opening balance (provisions that existed at period start)
            if ($provision->created_at <= $startDate) {
                // Get balance at period start (need to calculate from movements)
                $openingBalance += $this->getBalanceAtDate($provision, $startDate);
            }

            // Movements during the period
            $periodMovements = $provision->movements()
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->get();

            foreach ($periodMovements as $movement) {
                switch ($movement->movement_type) {
                    case 'initial_recognition':
                        $additions += $movement->home_amount;
                        break;
                    case 'remeasure_increase':
                        $remeasurements += $movement->home_amount;
                        break;
                    case 'remeasure_decrease':
                        $reversals += abs($movement->home_amount);
                        break;
                    case 'unwinding':
                        $unwinding += $movement->home_amount;
                        break;
                    case 'utilisation':
                        $utilisations += $movement->home_amount;
                        break;
                }
            }

            // Closing balance
            if ($provision->created_at <= $endDate) {
                $closingBalance += $provision->current_balance;
            }
        }

        return [
            'opening_balance' => $openingBalance,
            'additions' => $additions,
            'utilisations' => $utilisations,
            'reversals' => $reversals,
            'unwinding' => $unwinding,
            'remeasurements' => $remeasurements,
            'closing_balance' => $closingBalance,
            'count' => $provisions->count(),
        ];
    }

    /**
     * Calculate total disclosure across all provision types
     */
    private function calculateTotalDisclosure($provisions, Carbon $startDate, Carbon $endDate): array
    {
        $openingBalance = 0;
        $additions = 0;
        $utilisations = 0;
        $reversals = 0;
        $unwinding = 0;
        $remeasurements = 0;
        $closingBalance = 0;

        foreach ($provisions as $provision) {
            if ($provision->created_at <= $startDate) {
                $openingBalance += $this->getBalanceAtDate($provision, $startDate);
            }

            $periodMovements = $provision->movements()
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->get();

            foreach ($periodMovements as $movement) {
                switch ($movement->movement_type) {
                    case 'initial_recognition':
                        $additions += $movement->home_amount;
                        break;
                    case 'remeasure_increase':
                        $remeasurements += $movement->home_amount;
                        break;
                    case 'remeasure_decrease':
                        $reversals += abs($movement->home_amount);
                        break;
                    case 'unwinding':
                        $unwinding += $movement->home_amount;
                        break;
                    case 'utilisation':
                        $utilisations += $movement->home_amount;
                        break;
                }
            }

            if ($provision->created_at <= $endDate) {
                $closingBalance += $provision->current_balance;
            }
        }

        return [
            'opening_balance' => $openingBalance,
            'additions' => $additions,
            'utilisations' => $utilisations,
            'reversals' => $reversals,
            'unwinding' => $unwinding,
            'remeasurements' => $remeasurements,
            'closing_balance' => $closingBalance,
            'net_change' => $closingBalance - $openingBalance,
        ];
    }

    /**
     * Get provision balance at a specific date
     */
    private function getBalanceAtDate(Provision $provision, Carbon $date): float
    {
        // Get the last movement before or on the date
        $lastMovement = $provision->movements()
            ->where('movement_date', '<=', $date)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastMovement ? (float) $lastMovement->balance_after_movement : 0;
    }

    /**
     * Calculate movements summary
     */
    private function calculateMovements($provisions, Carbon $startDate, Carbon $endDate): array
    {
        $movements = [
            'initial_recognition' => 0,
            'remeasure_increase' => 0,
            'remeasure_decrease' => 0,
            'unwinding' => 0,
            'utilisation' => 0,
        ];

        foreach ($provisions as $provision) {
            $periodMovements = $provision->movements()
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->get();

            foreach ($periodMovements as $movement) {
                if (isset($movements[$movement->movement_type])) {
                    $movements[$movement->movement_type] += $movement->home_amount;
                }
            }
        }

        return $movements;
    }

    /**
     * Export disclosure to array format (for JSON/Excel)
     */
    public function exportToArray(int $companyId, string $periodStart, string $periodEnd): array
    {
        $disclosure = $this->generateDisclosure($companyId, $periodStart, $periodEnd);

        return [
            'period' => $disclosure['period'],
            'summary' => $disclosure['total_provisions'],
            'by_type' => $disclosure['provisions_by_type']->map(function ($typeData, $type) {
                return array_merge(['type' => $type], $typeData);
            })->values()->toArray(),
            'contingencies' => $disclosure['contingencies']->toArray(),
        ];
    }
}

