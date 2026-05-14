<?php

namespace App\Services\ProvisionComputation;

/**
 * Null computation service for provision types that don't require computation
 */
class NullComputationService implements ProvisionComputationInterface
{
    public function calculate(array $inputs): array
    {
        return [
            'amount' => 0,
            'assumptions' => [],
            'errors' => ['Computation not available for this provision type'],
        ];
    }

    public function getInputFields(): array
    {
        return [];
    }
}

