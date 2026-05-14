<?php

namespace App\Services\ProvisionComputation;

use Illuminate\Support\Facades\Validator;

class OnerousComputationService implements ProvisionComputationInterface
{
    /**
     * Calculate onerous contract provision using: MIN(Cost to Fulfill, Penalty to Exit)
     *
     * @param array $inputs
     * @return array ['amount' => float, 'assumptions' => array, 'errors' => array]
     */
    public function calculate(array $inputs): array
    {
        $validator = Validator::make($inputs, [
            'cost_to_fulfill' => 'required|numeric|min:0.01',
            'penalty_to_exit' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return [
                'amount' => 0,
                'assumptions' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        $costToFulfill = (float) $inputs['cost_to_fulfill'];
        $penaltyToExit = (float) $inputs['penalty_to_exit'];

        // Formula: MIN(Cost to Fulfill, Penalty to Exit)
        $provisionAmount = min($costToFulfill, $penaltyToExit);
        $selectedMethod = $provisionAmount === $costToFulfill ? 'cost_to_fulfill' : 'penalty_to_exit';

        $assumptions = [
            'formula' => 'MIN(Cost to Fulfill, Penalty to Exit)',
            'cost_to_fulfill' => $costToFulfill,
            'penalty_to_exit' => $penaltyToExit,
            'selected_method' => $selectedMethod,
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
                'name' => 'cost_to_fulfill',
                'label' => 'Cost to Fulfill Contract',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0.01,
                'help_text' => 'Total unavoidable cost to complete the contract',
            ],
            [
                'name' => 'penalty_to_exit',
                'label' => 'Penalty to Exit Contract',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0.01,
                'help_text' => 'Total penalty cost to terminate the contract early',
            ],
        ];
    }
}

