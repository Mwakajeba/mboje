<?php

namespace App\Services\ProvisionComputation;

interface ProvisionComputationInterface
{
    /**
     * Calculate provision amount based on inputs
     *
     * @param array $inputs
     * @return array ['amount' => float, 'assumptions' => array, 'errors' => array]
     */
    public function calculate(array $inputs): array;

    /**
     * Get input fields definition for UI
     *
     * @return array
     */
    public function getInputFields(): array;
}

