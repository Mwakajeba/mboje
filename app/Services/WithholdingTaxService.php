<?php

namespace App\Services;

/**
 * Withholding Tax Calculation Service
 * 
 * Handles WHT calculations for Exclusive, Inclusive, and Gross-Up treatments
 * Integrates with VAT handling per TRA regulations
 */
class WithholdingTaxService
{
    /**
     * Calculate WHT based on treatment type with VAT integration
     * 
     * @param float $totalAmount Total invoice/payment amount (may include VAT)
     * @param float $whtRate WHT rate (as percentage, e.g., 10 for 10%)
     * @param string $whtTreatment Treatment type: 'EXCLUSIVE', 'INCLUSIVE', 'GROSS_UP', 'NONE'
     * @param string $vatMode VAT mode: 'EXCLUSIVE', 'INCLUSIVE', 'NONE'
     * @param float|null $vatRate VAT rate (as percentage, e.g., 18 for 18%)
     * @return array ['base_amount' => float, 'vat_amount' => float, 'wht_amount' => float, 'net_payable' => float, 'total_cost' => float]
     */
    public function calculateWHT(
        float $totalAmount,
        float $whtRate,
        string $whtTreatment = 'EXCLUSIVE',
        string $vatMode = 'EXCLUSIVE',
        ?float $vatRate = null
    ): array {
        // Normalize rates (handle both percentage and decimal formats)
        $whtRate = $whtRate >= 1 ? $whtRate / 100 : $whtRate;
        $vatRate = $vatRate ? ($vatRate >= 1 ? $vatRate / 100 : $vatRate) : 0.18; // Default 18%

        // Step 1: Calculate base amount (excluding VAT)
        // Per TRA: WHT never applies on VAT portion
        $baseAmount = $totalAmount;
        $vatAmount = 0;

        if ($vatMode === 'INCLUSIVE' && $vatRate > 0) {
            // VAT is included in total, need to extract base
            // Total = Base × (1 + VAT Rate)
            // Base = Total ÷ (1 + VAT Rate)
            $baseAmount = round($totalAmount / (1 + $vatRate), 2);
            $vatAmount = round($totalAmount - $baseAmount, 2);
        } elseif ($vatMode === 'EXCLUSIVE' && $vatRate > 0) {
            // VAT is separate, total is base + VAT
            // If total includes VAT, extract base
            // Otherwise, total is already base
            // For now, assume total is base + VAT
            $baseAmount = round($totalAmount / (1 + $vatRate), 2);
            $vatAmount = round($totalAmount - $baseAmount, 2);
        } elseif ($vatMode === 'NONE' || $vatRate == 0) {
            // No VAT, total is base
            $baseAmount = $totalAmount;
            $vatAmount = 0;
        }

        // Step 2: Calculate WHT on base amount (never on VAT)
        $whtAmount = 0;
        $netPayable = $baseAmount;
        $totalCost = $baseAmount;

        switch (strtoupper($whtTreatment)) {
            case 'EXCLUSIVE':
                // WHT deducted from base amount
                // Formula: WHT = Base × Rate
                // Supplier receives: Base - WHT
                $whtAmount = round($baseAmount * $whtRate, 2);
                $netPayable = $baseAmount - $whtAmount;
                $totalCost = $baseAmount; // Total cost = base (payer pays base, supplier gets net)
                break;

            case 'INCLUSIVE':
                // WHT is already part of the total agreed amount
                // Formula: WHT = Base × (Rate / (1 + Rate))
                // Supplier receives: Base - WHT
                $whtAmount = round($baseAmount * ($whtRate / (1 + $whtRate)), 2);
                $netPayable = $baseAmount - $whtAmount;
                $totalCost = $baseAmount; // Total cost = base (agreed amount includes WHT)
                break;

            case 'GROSS_UP':
                // WHT is added on top of base amount
                // Formula: WHT = Base × (Rate / (1 - Rate))
                // Supplier receives: Base (full amount)
                // Payer pays: Base + WHT
                $whtAmount = round($baseAmount * ($whtRate / (1 - $whtRate)), 2);
                $netPayable = $baseAmount; // Supplier gets full base amount
                $totalCost = $baseAmount + $whtAmount; // Payer pays base + WHT
                break;

            case 'NONE':
            default:
                $whtAmount = 0;
                $netPayable = $baseAmount;
                $totalCost = $baseAmount;
                break;
        }

        return [
            'base_amount' => $baseAmount,
            'vat_amount' => $vatAmount,
            'wht_amount' => $whtAmount,
            'net_payable' => $netPayable,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Calculate WHT for AR (Receipts) - simpler version
     * 
     * @param float $totalAmount Total receipt amount
     * @param float $whtRate WHT rate (as percentage)
     * @param string $whtTreatment Treatment type: 'EXCLUSIVE', 'INCLUSIVE', 'NONE'
     * @param string $vatMode VAT mode: 'EXCLUSIVE', 'INCLUSIVE', 'NONE'
     * @param float|null $vatRate VAT rate (as percentage)
     * @return array ['base_amount' => float, 'vat_amount' => float, 'wht_amount' => float, 'net_receivable' => float]
     */
    public function calculateWHTForAR(
        float $totalAmount,
        float $whtRate,
        string $whtTreatment = 'EXCLUSIVE',
        string $vatMode = 'EXCLUSIVE',
        ?float $vatRate = null
    ): array {
        // Normalize rates
        $whtRate = $whtRate >= 1 ? $whtRate / 100 : $whtRate;
        $vatRate = $vatRate ? ($vatRate >= 1 ? $vatRate / 100 : $vatRate) : 0.18;

        // Calculate base amount (excluding VAT)
        $baseAmount = $totalAmount;
        $vatAmount = 0;

        if ($vatMode === 'INCLUSIVE' && $vatRate > 0) {
            $baseAmount = round($totalAmount / (1 + $vatRate), 2);
            $vatAmount = round($totalAmount - $baseAmount, 2);
        } elseif ($vatMode === 'EXCLUSIVE' && $vatRate > 0) {
            // VAT is exclusive: totalAmount IS the base amount, VAT is added separately
            $baseAmount = $totalAmount;
            $vatAmount = round($totalAmount * $vatRate, 2);
        }

        // Calculate WHT on base
        $whtAmount = 0;
        $netReceivable = $baseAmount;

        switch (strtoupper($whtTreatment)) {
            case 'EXCLUSIVE':
                $whtAmount = round($baseAmount * $whtRate, 2);
                $netReceivable = $baseAmount - $whtAmount;
                break;

            case 'INCLUSIVE':
                $whtAmount = round($baseAmount * ($whtRate / (1 + $whtRate)), 2);
                $netReceivable = $baseAmount - $whtAmount;
                break;

            case 'NONE':
            default:
                $whtAmount = 0;
                $netReceivable = $baseAmount;
                break;
        }

        return [
            'base_amount' => $baseAmount,
            'vat_amount' => $vatAmount,
            'wht_amount' => $whtAmount,
            'net_receivable' => $netReceivable,
        ];
    }

    /**
     * Get default WHT treatment for a supplier
     * 
     * @param int|null $supplierId
     * @return string
     */
    public function getDefaultTreatment(?int $supplierId = null): string
    {
        if ($supplierId) {
            $supplier = \App\Models\Supplier::find($supplierId);
            if ($supplier && $supplier->allow_gross_up) {
                return 'GROSS_UP';
            }
        }
        return 'EXCLUSIVE'; // Default treatment
    }

    /**
     * Validate WHT treatment for AR (Receipts)
     * Gross-Up is not applicable for AR since customer is withholding
     * 
     * @param string $treatment
     * @return bool
     */
    public function isValidARTreatment(string $treatment): bool
    {
        $validTreatments = ['EXCLUSIVE', 'INCLUSIVE', 'NONE'];
        return in_array(strtoupper($treatment), $validTreatments);
    }

    /**
     * Validate WHT treatment for AP (Payments)
     * 
     * @param string $treatment
     * @return bool
     */
    public function isValidAPTreatment(string $treatment): bool
    {
        $validTreatments = ['EXCLUSIVE', 'INCLUSIVE', 'GROSS_UP', 'NONE'];
        return in_array(strtoupper($treatment), $validTreatments);
    }
}
