<?php

namespace App\Services\ProvisionComputation;

use InvalidArgumentException;

class ProvisionComputationFactory
{
    /**
     * Get the appropriate computation service for a provision type
     *
     * @param string $provisionType
     * @return ProvisionComputationInterface
     * @throws InvalidArgumentException
     */
    public static function getService(string $provisionType): ProvisionComputationInterface
    {
        return match ($provisionType) {
            'warranty' => new WarrantyComputationService(),
            'onerous_contract' => new OnerousComputationService(),
            'environmental' => new EnvironmentalComputationService(),
            'restructuring' => new RestructuringComputationService(),
            'legal_claim' => new LegalComputationService(),
            'employee_benefit', 'other' => new NullComputationService(), // No computation for these
            default => throw new InvalidArgumentException("Unknown provision type: {$provisionType}"),
        };
    }

    /**
     * Check if a provision type has computation enabled
     */
    public static function hasComputation(string $provisionType): bool
    {
        $templates = config('ias37_provision_templates', []);
        return isset($templates[$provisionType]['computation']['enabled']) 
            && $templates[$provisionType]['computation']['enabled'] === true;
    }
}

