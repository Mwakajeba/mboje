<?php

namespace Database\Factories;

use App\Models\Assets\HfsDisposal;
use App\Models\Assets\HfsRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assets\HfsDisposal>
 */
class HfsDisposalFactory extends Factory
{
    protected $model = HfsDisposal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $saleProceeds = $this->faker->randomFloat(2, 50000, 500000);
        $carryingAmount = $this->faker->randomFloat(2, 40000, 400000);
        $costsSold = $this->faker->randomFloat(2, 1000, 10000);
        $gainLoss = $saleProceeds - $carryingAmount - $costsSold;

        return [
            'hfs_id' => HfsRequest::factory(),
            'disposal_date' => now(),
            'sale_proceeds' => $saleProceeds,
            'sale_currency' => 'USD',
            'currency_rate' => 1.0,
            'costs_sold' => $costsSold,
            'carrying_amount_at_disposal' => $carryingAmount,
            'accumulated_impairment_at_disposal' => 0,
            'gain_loss_amount' => $gainLoss,
            'buyer_name' => $this->faker->company(),
            'buyer_contact' => $this->faker->phoneNumber(),
            'buyer_address' => $this->faker->address(),
            'invoice_number' => $this->faker->unique()->numerify('INV-####'),
            'receipt_number' => $this->faker->unique()->numerify('RCP-####'),
            'settlement_reference' => $this->faker->unique()->numerify('SET-####'),
            'bank_account_id' => null,
            'vat_amount' => 0,
            'withholding_tax' => 0,
            'is_partial_sale' => false,
            'partial_sale_percentage' => null,
            'notes' => $this->faker->sentence(),
            'attachments' => null,
            'gl_posted' => false,
            'gl_posted_at' => null,
            'journal_id' => null,
            'created_by' => null,
        ];
    }
}
