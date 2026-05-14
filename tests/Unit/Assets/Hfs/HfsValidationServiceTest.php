<?php

namespace Tests\Unit\Assets\Hfs;

use Tests\TestCase;
use App\Services\Assets\Hfs\HfsValidationService;
use App\Models\Assets\HfsRequest;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class HfsValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $validationService;
    protected $company;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validationService = new HfsValidationService();
        
        // Create test company and user
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_validates_ifrs5_criteria_for_hfs_request()
    {
        $category = AssetCategory::factory()->create(['company_id' => $this->company->id]);
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $category->id,
            'status' => 'active',
        ]);

        $hfsRequest = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'initiator_id' => $this->user->id,
            'intended_sale_date' => now()->addMonths(6),
            'management_committed' => true,
            'management_commitment_date' => now(),
            'buyer_name' => 'Test Buyer',
            'marketing_actions' => 'Active marketing program',
            'expected_fair_value' => 100000,
            'probability_pct' => 80,
            'attachments' => ['management_minutes.pdf'],
        ]);

        $result = $this->validationService->validateIfrs5Criteria($hfsRequest);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
    }

    /** @test */
    public function it_requires_management_commitment_evidence()
    {
        $hfsRequest = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'initiator_id' => $this->user->id,
            'management_committed' => false,
            'attachments' => null,
        ]);

        $result = $this->validationService->validateIfrs5Criteria($hfsRequest);

        $this->assertFalse($result['valid']);
        $this->assertContains('Management commitment evidence is required', $result['errors']);
    }

    /** @test */
    public function it_validates_12_month_timeline()
    {
        // Sale within 12 months - should be valid
        $hfsRequest = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'initiator_id' => $this->user->id,
            'intended_sale_date' => now()->addMonths(6),
            'exceeds_12_months' => false,
        ]);

        $result = $this->validationService->validateIfrs5Criteria($hfsRequest);
        
        // Should not have timeline errors (may have other errors)
        $timelineErrors = array_filter($result['errors'], function($error) {
            return strpos($error, '12 months') !== false || strpos($error, 'timeline') !== false;
        });
        $this->assertEmpty($timelineErrors);

        // Sale beyond 12 months without approval - should fail
        $hfsRequest2 = HfsRequest::factory()->create([
            'company_id' => $this->company->id,
            'initiator_id' => $this->user->id,
            'intended_sale_date' => now()->addMonths(15),
            'exceeds_12_months' => false,
            'extension_justification' => null,
        ]);

        $result2 = $this->validationService->validateIfrs5Criteria($hfsRequest2);
        $this->assertFalse($result2['valid']);
    }

    /** @test */
    public function it_validates_asset_eligibility()
    {
        $category = AssetCategory::factory()->create(['company_id' => $this->company->id]);
        
        // Active asset - should be eligible
        $activeAsset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $category->id,
            'status' => 'active',
            'hfs_status' => 'none',
        ]);

        $result = $this->validationService->validateAssetEligibility($activeAsset);
        $this->assertTrue($result['valid']);

        // Disposed asset - should not be eligible
        $disposedAsset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $category->id,
            'status' => 'disposed',
        ]);

        $result2 = $this->validationService->validateAssetEligibility($disposedAsset);
        $this->assertFalse($result2['valid']);
        $this->assertContains('already disposed', $result2['errors']);
    }

    /** @test */
    public function it_checks_bank_consent_for_pledged_assets()
    {
        $category = AssetCategory::factory()->create(['company_id' => $this->company->id]);
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'asset_category_id' => $category->id,
            'is_pledged' => true,
            'bank_consent_attachment' => null,
        ]);

        $requiresConsent = $this->validationService->requiresBankConsent($asset);
        $this->assertTrue($requiresConsent);
    }
}

