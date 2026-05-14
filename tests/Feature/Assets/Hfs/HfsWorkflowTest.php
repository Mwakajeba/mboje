<?php

namespace Tests\Feature\Assets\Hfs;

use Tests\TestCase;
use App\Models\Assets\HfsRequest;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Company;
use App\Models\User;
use App\Models\ChartAccount;
use App\Models\Journal;
use App\Models\GlTransaction;
use App\Services\Assets\Hfs\HfsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class HfsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $hfsService;
    protected $category;
    protected $assetAccount;
    protected $hfsAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Create chart accounts
        $this->assetAccount = ChartAccount::factory()->create([
            'company_id' => $this->company->id,
            'account_name' => 'PPE Assets',
        ]);
        
        $this->hfsAccount = ChartAccount::factory()->create([
            'company_id' => $this->company->id,
            'account_name' => 'Assets Held for Sale',
        ]);
        
        $this->category = AssetCategory::factory()->create([
            'company_id' => $this->company->id,
            'asset_account_id' => $this->assetAccount->id,
            'hfs_account_id' => $this->hfsAccount->id,
        ]);
        
        $this->hfsService = app(HfsService::class);
    }

    /** @test */
    public function it_completes_full_hfs_workflow()
    {
        // Create asset
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $this->category->id,
            'status' => 'active',
            'purchase_cost' => 100000,
            'current_nbv' => 80000,
        ]);

        // Step 1: Create HFS request
        $hfsRequest = $this->hfsService->createHfsRequest([
            'asset_ids' => [$asset->id],
            'intended_sale_date' => now()->addMonths(6),
            'expected_close_date' => now()->addMonths(7),
            'buyer_name' => 'Test Buyer',
            'justification' => 'Strategic disposal',
            'expected_fair_value' => 90000,
            'expected_costs_to_sell' => 5000,
            'management_committed' => true,
            'management_commitment_date' => now(),
            'marketing_actions' => 'Active marketing',
            'probability_pct' => 85,
            'attachments' => ['management_minutes.pdf'],
        ]);

        $this->assertNotNull($hfsRequest);
        $this->assertEquals('draft', $hfsRequest->status);
        $this->assertCount(1, $hfsRequest->hfsAssets);

        // Step 2: Approve HFS request
        $approvalResult = $this->hfsService->approveHfsRequest(
            $hfsRequest,
            'finance_manager',
            $this->user->id,
            'Approved for HFS classification'
        );

        $this->assertTrue($approvalResult['success']);
        
        // Refresh asset
        $asset->refresh();
        $this->assertEquals('classified', $asset->hfs_status);
        $this->assertTrue($asset->depreciation_stopped);

        // Verify journal was created
        $journal = Journal::where('reference', $hfsRequest->request_no)->first();
        $this->assertNotNull($journal);
        
        // Verify GL transactions
        $glTransactions = GlTransaction::where('transaction_id', $journal->id)->get();
        $this->assertGreaterThan(0, $glTransactions->count());

        // Step 3: Measure HFS (impairment)
        $measurementResult = $this->hfsService->measureHfs($hfsRequest, [
            'fair_value' => 75000,
            'costs_to_sell' => 5000,
            'valuation_date' => now(),
        ]);

        $this->assertTrue($measurementResult['success']);
        $this->assertGreaterThan(0, $measurementResult['impairment_amount']);

        // Step 4: Process sale
        $saleResult = $this->hfsService->processSale($hfsRequest, [
            'disposal_date' => now(),
            'sale_proceeds' => 80000,
            'costs_sold' => 3000,
            'buyer_name' => 'Test Buyer',
        ]);

        $this->assertTrue($saleResult['success']);
        
        // Verify asset is disposed
        $asset->refresh();
        $this->assertEquals('disposed', $asset->status);
        $this->assertEquals('sold', $asset->hfs_status);
    }

    /** @test */
    public function it_prevents_depreciation_for_hfs_assets()
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $this->category->id,
            'status' => 'active',
            'depreciation_stopped' => false,
        ]);

        $hfsRequest = $this->hfsService->createHfsRequest([
            'asset_ids' => [$asset->id],
            'intended_sale_date' => now()->addMonths(6),
            'justification' => 'Test',
            'expected_fair_value' => 90000,
            'management_committed' => true,
            'management_commitment_date' => now(),
            'marketing_actions' => 'Active marketing',
        ]);

        $this->hfsService->approveHfsRequest($hfsRequest, 'finance_manager', $this->user->id);

        $asset->refresh();
        $this->assertTrue($asset->depreciation_stopped);
    }

    /** @test */
    public function it_handles_cancellation_correctly()
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $this->category->id,
            'status' => 'active',
        ]);

        $hfsRequest = $this->hfsService->createHfsRequest([
            'asset_ids' => [$asset->id],
            'intended_sale_date' => now()->addMonths(6),
            'justification' => 'Test',
            'expected_fair_value' => 90000,
            'management_committed' => true,
            'management_commitment_date' => now(),
            'marketing_actions' => 'Active marketing',
        ]);

        $this->hfsService->approveHfsRequest($hfsRequest, 'finance_manager', $this->user->id);
        
        $asset->refresh();
        $this->assertTrue($asset->depreciation_stopped);

        // Cancel HFS
        $cancelResult = $this->hfsService->cancelHfs($hfsRequest, 'Sale cancelled');

        $this->assertTrue($cancelResult['success']);
        
        $asset->refresh();
        $this->assertEquals('none', $asset->hfs_status);
        $this->assertFalse($asset->depreciation_stopped);
    }
}

