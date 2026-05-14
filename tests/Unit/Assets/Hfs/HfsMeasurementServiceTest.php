<?php

namespace Tests\Unit\Assets\Hfs;

use Tests\TestCase;
use App\Services\Assets\Hfs\HfsMeasurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HfsMeasurementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $measurementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->measurementService = new HfsMeasurementService();
    }

    /** @test */
    public function it_calculates_fv_less_costs_correctly()
    {
        $fairValue = 100000;
        $costsToSell = 5000;
        
        $result = $this->measurementService->calculateFvLessCosts($fairValue, $costsToSell);
        
        $this->assertEquals(95000, $result);
    }

    /** @test */
    public function it_calculates_impairment_when_fv_less_costs_is_lower()
    {
        $carryingAmount = 100000;
        $fvLessCosts = 80000;
        
        $impairment = $this->measurementService->calculateImpairment($carryingAmount, $fvLessCosts);
        
        $this->assertEquals(20000, $impairment);
    }

    /** @test */
    public function it_returns_zero_impairment_when_fv_less_costs_is_higher()
    {
        $carryingAmount = 100000;
        $fvLessCosts = 120000;
        
        $impairment = $this->measurementService->calculateImpairment($carryingAmount, $fvLessCosts);
        
        $this->assertEquals(0, $impairment);
    }

    /** @test */
    public function it_calculates_reversal_limited_to_original_carrying()
    {
        $currentCarryingAmount = 80000; // After impairment
        $fvLessCosts = 95000;
        $originalCarryingBeforeImpairment = 100000;
        
        $reversal = $this->measurementService->calculateReversal(
            $currentCarryingAmount,
            $fvLessCosts,
            $originalCarryingBeforeImpairment
        );
        
        // Reversal should be limited to bring carrying back to original (100000)
        // So reversal = min(95000 - 80000, 100000 - 80000) = min(15000, 20000) = 15000
        $this->assertEquals(15000, $reversal);
    }

    /** @test */
    public function it_limits_reversal_to_original_carrying_amount()
    {
        $currentCarryingAmount = 80000;
        $fvLessCosts = 110000; // Higher than original
        $originalCarryingBeforeImpairment = 100000;
        
        $reversal = $this->measurementService->calculateReversal(
            $currentCarryingAmount,
            $fvLessCosts,
            $originalCarryingBeforeImpairment
        );
        
        // Reversal should be limited to original carrying (100000 - 80000 = 20000)
        $this->assertEquals(20000, $reversal);
    }
}

