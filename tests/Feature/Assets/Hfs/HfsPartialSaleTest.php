<?php

namespace Tests\Feature\Assets\Hfs;

use Tests\TestCase;
use App\Services\Assets\Hfs\HfsPartialSaleService;
use App\Models\Assets\HfsRequest;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HfsPartialSaleTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $partialSaleService;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->partialSaleService = new HfsPartialSaleService();
        $this->category = AssetCategory::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_processes_partial_sale_of_disposal_group()
    {
        // Create disposal group with multiple assets
        $asset1 = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $this->category->id,
            'current_nbv' => 50000,
        ]);

        $asset2 = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $this->category->id,
            'current_nbv' => 50000,
        ]);

        $hfsRequest = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'initiator_id' => $this->user->id,
            'is_disposal_group' => true,
            'status' => 'approved',
        ]);

        // Create HFS assets
        $hfsAsset1 = \App\Models\Assets\HfsAsset::factory()->create([
            'hfs_id' => $hfsRequest->id,
            'asset_id' => $asset1->id,
            'current_carrying_amount' => 50000,
            'status' => 'classified',
        ]);

        $hfsAsset2 = \App\Models\Assets\HfsAsset::factory()->create([
            'hfs_id' => $hfsRequest->id,
            'asset_id' => $asset2->id,
            'current_carrying_amount' => 50000,
            'status' => 'classified',
        ]);

        // Process partial sale (sell only asset1)
        $result = $this->partialSaleService->processPartialSale($hfsRequest, [
            'sale_proceeds' => 60000,
            'disposal_date' => now(),
            'assets_sold' => [$asset1->id],
            'reclassify_remaining' => false,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['assets_sold']);
        $this->assertEquals(1, $result['assets_remaining']);

        // Verify asset1 is disposed
        $asset1->refresh();
        $this->assertEquals('disposed', $asset1->status);

        // Verify asset2 remains in HFS
        $asset2->refresh();
        $this->assertEquals('classified', $asset2->hfs_status);
    }

    /** @test */
    public function it_validates_partial_sale_requirements()
    {
        $hfsRequest = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'is_disposal_group' => false, // Not a disposal group
        ]);

        $result = $this->partialSaleService->validatePartialSale($hfsRequest, [
            'sale_proceeds' => 50000,
        ]);

        $this->assertFalse($result['valid']);
        $this->assertContains('disposal group', $result['errors']);
    }
}

