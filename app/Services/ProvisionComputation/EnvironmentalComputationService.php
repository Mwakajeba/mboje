<?php

namespace App\Services\ProvisionComputation;

use Illuminate\Support\Facades\Validator;

class EnvironmentalComputationService implements ProvisionComputationInterface
{
    /**
     * Calculate environmental provision using Present Value: PV = Future Cost / (1 + r)^n
     *
     * @param array $inputs
     * @return array ['amount' => float, 'undiscounted_amount' => float, 'assumptions' => array, 'errors' => array]
     */
    public function calculate(array $inputs): array
    {
        $validator = Validator::make($inputs, [
            'future_cost' => 'required|numeric|min:0.01',
            'settlement_year' => 'required|integer|min:' . date('Y'),
            'discount_rate_percent' => 'required|numeric|min:0|max:100',
            'inflation_assumption' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return [
                'amount' => 0,
                'undiscounted_amount' => 0,
                'assumptions' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        $futureCost = (float) $inputs['future_cost'];
        $settlementYear = (int) $inputs['settlement_year'];
        $discountRate = (float) $inputs['discount_rate_percent'] / 100; // Convert % to decimal
        $inflationRate = isset($inputs['inflation_assumption']) ? (float) $inputs['inflation_assumption'] / 100 : 0;

        // Calculate years to settlement
        $currentYear = (int) date('Y');
        $yearsToSettlement = max(1, $settlementYear - $currentYear);

        // Apply inflation if provided (adjust future cost)
        if ($inflationRate > 0) {
            $futureCost = $futureCost * pow(1 + $inflationRate, $yearsToSettlement);
        }

        // Calculate Present Value: PV = Future Cost / (1 + r)^n
        $presentValue = $futureCost / pow(1 + $discountRate, $yearsToSettlement);

        $assumptions = [
            'formula' => 'PV = Future Cost / (1 + r)^n',
            'future_cost' => $inputs['future_cost'],
            'settlement_year' => $settlementYear,
            'years_to_settlement' => $yearsToSettlement,
            'discount_rate_percent' => $inputs['discount_rate_percent'],
            'inflation_assumption' => $inputs['inflation_assumption'] ?? null,
            'future_cost_adjusted' => $inflationRate > 0 ? round($futureCost, 2) : null,
            'present_value' => round($presentValue, 2),
            'undiscounted_amount' => round($futureCost, 2),
            'calculation_date' => now()->toDateString(),
        ];

        return [
            'amount' => round($presentValue, 2),
            'undiscounted_amount' => round($futureCost, 2),
            'assumptions' => $assumptions,
            'errors' => [],
        ];
    }

    /**
     * Get computation input fields for UI
     */
    public function getInputFields(): array
    {
        return [
            [
                'name' => 'future_cost',
                'label' => 'Expected Future Restoration Cost',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0.01,
                'help_text' => 'Estimated cost of restoration at settlement date (undiscounted)',
            ],
            [
                'name' => 'settlement_year',
                'label' => 'Expected Settlement Year',
                'type' => 'number',
                'required' => true,
                'min' => date('Y'),
                'help_text' => 'Year when restoration is expected to occur',
            ],
            [
                'name' => 'discount_rate_percent',
                'label' => 'Discount Rate (%)',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0,
                'max' => 100,
                'help_text' => 'Pre-tax discount rate (e.g., 12.5 for 12.5%)',
            ],
            [
                'name' => 'inflation_assumption',
                'label' => 'Inflation Assumption (%) (Optional)',
                'type' => 'number',
                'required' => false,
                'step' => '0.01',
                'min' => 0,
                'max' => 100,
                'help_text' => 'Annual inflation rate to adjust future cost (if applicable)',
            ],
        ];
    }
}

