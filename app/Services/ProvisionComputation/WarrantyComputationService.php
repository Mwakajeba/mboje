<?php

namespace App\Services\ProvisionComputation;

use Illuminate\Support\Facades\Validator;

class WarrantyComputationService implements ProvisionComputationInterface
{
    /**
     * Calculate warranty provision using: Units Sold × Defect Rate % × Average Repair Cost
     *
     * @param array $inputs
     * @return array ['amount' => float, 'assumptions' => array, 'errors' => array]
     */
    public function calculate(array $inputs): array
    {
        $validator = Validator::make($inputs, [
            'units_sold' => 'required|integer|min:1',
            'defect_rate_percent' => 'required|numeric|min:0|max:100',
            'average_repair_cost' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return [
                'amount' => 0,
                'assumptions' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        $unitsSold = (int) $inputs['units_sold'];
        $defectRate = (float) $inputs['defect_rate_percent'] / 100; // Convert % to decimal
        $avgRepairCost = (float) $inputs['average_repair_cost'];

        // Formula: Units Sold × Defect Rate % × Average Repair Cost
        $provisionAmount = $unitsSold * $defectRate * $avgRepairCost;

        $assumptions = [
            'formula' => 'Units Sold × Defect Rate % × Average Repair Cost',
            'units_sold' => $unitsSold,
            'defect_rate_percent' => $inputs['defect_rate_percent'],
            'average_repair_cost' => $avgRepairCost,
            'calculated_amount' => round($provisionAmount, 2),
            'calculation_date' => now()->toDateString(),
        ];

        return [
            'amount' => round($provisionAmount, 2),
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
                'name' => 'units_sold',
                'label' => 'Units Sold',
                'type' => 'number',
                'required' => true,
                'help_text' => 'Total number of units sold in the period',
            ],
            [
                'name' => 'defect_rate_percent',
                'label' => 'Historical Defect Rate (%)',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0,
                'max' => 100,
                'help_text' => 'Historical percentage of units that require warranty repairs (e.g., 3.5 for 3.5%)',
            ],
            [
                'name' => 'average_repair_cost',
                'label' => 'Average Repair Cost',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0.01,
                'help_text' => 'Average cost per warranty repair',
            ],
        ];
    }
}

