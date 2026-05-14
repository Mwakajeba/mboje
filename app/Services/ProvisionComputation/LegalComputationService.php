<?php

namespace App\Services\ProvisionComputation;

use Illuminate\Support\Facades\Validator;

class LegalComputationService implements ProvisionComputationInterface
{
    /**
     * Calculate legal claim provision using Expected Value or Most Likely Outcome
     *
     * @param array $inputs
     * @return array ['amount' => float, 'assumptions' => array, 'errors' => array]
     */
    public function calculate(array $inputs): array
    {
        // Check which method is being used
        $method = $inputs['method'] ?? 'expected_value';

        if ($method === 'most_likely_outcome') {
            return $this->calculateMostLikelyOutcome($inputs);
        } else {
            return $this->calculateExpectedValue($inputs);
        }
    }

    /**
     * Calculate using Expected Value: Σ (Probability × Outcome)
     */
    private function calculateExpectedValue(array $inputs): array
    {
        $validator = Validator::make($inputs, [
            'outcomes' => 'required|array|min:1',
            'outcomes.*.amount' => 'required|numeric|min:0',
            'outcomes.*.probability_percent' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return [
                'amount' => 0,
                'assumptions' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        $outcomes = $inputs['outcomes'];
        $totalProbability = 0;
        $expectedValue = 0;

        foreach ($outcomes as $index => $outcome) {
            $amount = (float) $outcome['amount'];
            $probability = (float) $outcome['probability_percent'] / 100;
            $totalProbability += $probability;
            $expectedValue += $amount * $probability;
        }

        $errors = [];
        if (abs($totalProbability - 1.0) > 0.01) { // Allow small rounding differences
            $errors[] = "Total probability must equal 100%. Current total: " . round($totalProbability * 100, 2) . "%";
        }

        $assumptions = [
            'formula' => 'Σ (Probability × Outcome)',
            'method' => 'expected_value',
            'outcomes' => $outcomes,
            'total_probability' => round($totalProbability * 100, 2),
            'calculated_amount' => round($expectedValue, 2),
            'calculation_date' => now()->toDateString(),
        ];

        return [
            'amount' => round($expectedValue, 2),
            'assumptions' => $assumptions,
            'errors' => $errors,
        ];
    }

    /**
     * Calculate using Most Likely Outcome (single best estimate)
     */
    private function calculateMostLikelyOutcome(array $inputs): array
    {
        $validator = Validator::make($inputs, [
            'most_likely_amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return [
                'amount' => 0,
                'assumptions' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        $amount = (float) $inputs['most_likely_amount'];

        $assumptions = [
            'formula' => 'Most Likely Outcome',
            'method' => 'most_likely_outcome',
            'most_likely_amount' => $amount,
            'calculated_amount' => round($amount, 2),
            'calculation_date' => now()->toDateString(),
        ];

        return [
            'amount' => round($amount, 2),
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
                'name' => 'method',
                'label' => 'Calculation Method',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'expected_value' => 'Expected Value (Multiple Outcomes)',
                    'most_likely_outcome' => 'Most Likely Outcome (Single Estimate)',
                ],
                'help_text' => 'Choose calculation method based on available information',
            ],
            // These will be shown conditionally based on method selection
        ];
    }
}

