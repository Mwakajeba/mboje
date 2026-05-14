<?php

namespace Tests\Feature\Assets\Hfs;

use Tests\TestCase;
use App\Services\Assets\Hfs\HfsMultiCurrencyService;
use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsDisposal;
use App\Models\Assets\HfsAsset;
use App\Models\FxRate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HfsMultiCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $multiCurrencyService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->multiCurrencyService = new HfsMultiCurrencyService();
    }

    /** @test */
    public function it_converts_foreign_currency_to_functional_currency()
    {
        // Create FX rate
        FxRate::factory()->create([
            'company_id' => $this->company->id,
            'from_currency' => 'USD',
            'to_currency' => 'TZS',
            'rate_date' => now(),
            'spot_rate' => 2500.00,
        ]);

        $amount = 1000; // USD
        $result = $this->multiCurrencyService->convertToFunctionalCurrency(
            $amount,
            'USD',
            'TZS',
            now(),
            $this->company->id
        );

        $this->assertEquals(2500000, $result); // 1000 * 2500
    }

    /** @test */
    public function it_calculates_fx_gain_loss_on_disposal()
    {
        $hfsRequest = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'initiator_id' => $this->user->id,
        ]);

        $hfsAsset = HfsAsset::factory()->create([
            'hfs_id' => $hfsRequest->id,
            'book_currency' => 'TZS',
            'carrying_amount_at_reclass' => 2000000, // TZS
        ]);

        // Create disposal in USD
        $disposal = HfsDisposal::factory()->create([
            'hfs_id' => $hfsRequest->id,
            'sale_proceeds' => 1000, // USD
            'sale_currency' => 'USD',
            'currency_rate' => 2500, // USD to TZS
            'carrying_amount_at_disposal' => 2000000, // TZS
            'disposal_date' => now(),
        ]);

        // Create FX rate
        FxRate::factory()->create([
            'company_id' => $this->company->id,
            'from_currency' => 'USD',
            'to_currency' => 'TZS',
            'rate_date' => $disposal->disposal_date,
            'spot_rate' => 2500.00,
        ]);

        $fxData = $this->multiCurrencyService->calculateFxGainLoss($disposal);

        $this->assertArrayHasKey('fx_gain_loss', $fxData);
        $this->assertArrayHasKey('sale_proceeds_lcy', $fxData);
        $this->assertArrayHasKey('carrying_amount_lcy', $fxData);
    }
}

