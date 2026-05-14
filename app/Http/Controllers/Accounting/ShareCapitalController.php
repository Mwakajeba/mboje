<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Shares\ShareClass;
use App\Models\Shares\Shareholder;
use App\Models\Shares\ShareIssue;
use App\Models\Shares\ShareDividend;
use App\Models\Shares\ShareCorporateAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareCapitalController extends Controller
{
    /**
     * Display the Share Capital Management dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Calculate statistics
        $totalShareClasses = ShareClass::where('company_id', $companyId)->where('is_active', true)->count();
        $totalShareholders = Shareholder::where('company_id', $companyId)->where('is_active', true)->count();
        $totalIssues = ShareIssue::where('company_id', $companyId)->where('status', 'posted')->count();
        $totalDividends = ShareDividend::where('company_id', $companyId)->where('status', '!=', 'cancelled')->count();
        
        // Get recent activities
        $recentIssues = ShareIssue::where('company_id', $companyId)
            ->with('shareClass')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $recentDividends = ShareDividend::where('company_id', $companyId)
            ->with('shareClass')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('accounting.share-capital.index', compact(
            'totalShareClasses',
            'totalShareholders',
            'totalIssues',
            'totalDividends',
            'recentIssues',
            'recentDividends'
        ));
    }
}


