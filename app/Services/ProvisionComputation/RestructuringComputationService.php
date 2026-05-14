<?php

namespace App\Services\ProvisionComputation;

use Illuminate\Support\Facades\Validator;

class RestructuringComputationService implements ProvisionComputationInterface
{
    /**
     * Calculate restructuring provision: Employees × Average Termination Cost + Contract Penalties
     *
     * @param array $inputs
     * @return array ['amount' => float, 'assumptions' => array, 'errors' => array]
     */
    public function calculate(array $inputs): array
    {
        $validator = Validator::make($inputs, [
            'employees_affected' => 'required|integer|min:1',
            'average_termination_cost' => 'required|numeric|min:0.01',
            'contract_termination_penalties' => 'nullable|numeric|min:0',
            // Block excluded costs
            'training_costs' => 'nullable|numeric|min:0',
            'marketing_costs' => 'nullable|numeric|min:0',
            'future_operating_losses' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return [
                'amount' => 0,
                'assumptions' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        $employeesAffected = (int) $inputs['employees_affected'];
        $avgTerminationCost = (float) $inputs['average_termination_cost'];
        $contractPenalties = (float) ($inputs['contract_termination_penalties'] ?? 0);

        // Calculate termination benefits
        $terminationBenefits = $employeesAffected * $avgTerminationCost;

        // Total provision = Termination benefits + Contract penalties
        $provisionAmount = $terminationBenefits + $contractPenalties;

        // Validate excluded costs are not included
        $excludedCosts = [
            'training_costs' => (float) ($inputs['training_costs'] ?? 0),
            'marketing_costs' => (float) ($inputs['marketing_costs'] ?? 0),
            'future_operating_losses' => (float) ($inputs['future_operating_losses'] ?? 0),
        ];

        $errors = [];
        foreach ($excludedCosts as $costType => $amount) {
            if ($amount > 0) {
                $errors[] = ucfirst(str_replace('_', ' ', $costType)) . ' cannot be included in restructuring provisions per IAS 37.';
            }
        }

        $assumptions = [
            'formula' => 'Employees Affected × Average Termination Cost + Contract Penalties',
            'employees_affected' => $employeesAffected,
            'average_termination_cost' => $avgTerminationCost,
            'termination_benefits' => round($terminationBenefits, 2),
            'contract_termination_penalties' => $contractPenalties,
            'excluded_costs' => $excludedCosts,
            'calculated_amount' => round($provisionAmount, 2),
            'calculation_date' => now()->toDateString(),
        ];

        return [
            'amount' => round($provisionAmount, 2),
            'assumptions' => $assumptions,
            'errors' => $errors,
        ];
    }

    /**
     * Get computation input fields for UI
     */
    public function getInputFields(): array
    {
        return [
            [
                'name' => 'employees_affected',
                'label' => 'Employees Affected',
                'type' => 'number',
                'required' => true,
                'min' => 1,
                'help_text' => 'Number of employees to be terminated',
            ],
            [
                'name' => 'average_termination_cost',
                'label' => 'Average Termination Cost per Employee',
                'type' => 'number',
                'required' => true,
                'step' => '0.01',
                'min' => 0.01,
                'help_text' => 'Average cost per employee termination (severance, notice pay, etc.)',
            ],
            [
                'name' => 'contract_termination_penalties',
                'label' => 'Contract Termination Penalties (Optional)',
                'type' => 'number',
                'required' => false,
                'step' => '0.01',
                'min' => 0,
                'help_text' => 'Penalties for terminating contracts (leases, supply agreements, etc.)',
            ],
            // Note: Excluded costs are shown for information but blocked from calculation
            [
                'name' => 'training_costs',
                'label' => 'Training Costs (EXCLUDED - Not Allowed)',
                'type' => 'number',
                'required' => false,
                'step' => '0.01',
                'min' => 0,
                'readonly' => true,
                'help_text' => '⚠️ Training costs are NOT allowed in restructuring provisions per IAS 37',
                'class' => 'text-danger',
            ],
            [
                'name' => 'marketing_costs',
                'label' => 'Marketing Costs (EXCLUDED - Not Allowed)',
                'type' => 'number',
                'required' => false,
                'step' => '0.01',
                'min' => 0,
                'readonly' => true,
                'help_text' => '⚠️ Marketing costs are NOT allowed in restructuring provisions per IAS 37',
                'class' => 'text-danger',
            ],
            [
                'name' => 'future_operating_losses',
                'label' => 'Future Operating Losses (EXCLUDED - Not Allowed)',
                'type' => 'number',
                'required' => false,
                'step' => '0.01',
                'min' => 0,
                'readonly' => true,
                'help_text' => '⚠️ Future operating losses are NOT allowed in restructuring provisions per IAS 37',
                'class' => 'text-danger',
            ],
        ];
    }
}

