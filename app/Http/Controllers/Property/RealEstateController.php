<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Property;
use App\Models\Property\Lease;
use App\Models\Property\Tenant;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RealEstateController extends Controller
{
    public function index()
    {
        $this->authorize('view real estate');
        
        // Get summary statistics
        $totalProperties = Property::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereIn('type', ['building', 'apartment', 'house', 'commercial', 'office'])
            ->count();
            
        $totalUnits = Property::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereIn('type', ['building', 'apartment', 'house', 'commercial', 'office'])
            ->withCount('rooms')
            ->get()
            ->sum('rooms_count');
            
        $activeLeases = Lease::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->active()
            ->count();
            
        $totalTenants = Tenant::forCompany(current_company_id())
            ->active()
            ->count();
            
        $monthlyRentIncome = $this->calculateMonthlyRentIncome();
        $occupancyRate = $this->calculateOccupancyRate();
        $expiringLeases = $this->getExpiringLeases();

        return view('property.real-estate.index', compact(
            'totalProperties',
            'totalUnits',
            'activeLeases',
            'totalTenants',
            'monthlyRentIncome',
            'occupancyRate',
            'expiringLeases'
        ));
    }

    private function calculateMonthlyRentIncome()
    {
        return Lease::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->active()
            ->sum('monthly_rent');
    }

    private function calculateOccupancyRate()
    {
        $totalUnits = Property::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereIn('type', ['building', 'apartment', 'house', 'commercial', 'office'])
            ->withCount('rooms')
            ->get()
            ->sum('rooms_count');
            
        if ($totalUnits == 0) return 0;
        
        $occupiedUnits = Lease::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->active()
            ->count();
            
        return round(($occupiedUnits / $totalUnits) * 100, 2);
    }

    private function getExpiringLeases()
    {
        return Lease::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->expiringSoon(30)
            ->with(['property', 'tenant'])
            ->get();
    }
}