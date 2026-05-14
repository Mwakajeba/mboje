<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesProforma;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    /**
     * Display the sales dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get proforma statistics for the current branch
        $proformaStats = [
            'total' => SalesProforma::forBranch($user->branch_id)->count(),
            'draft' => SalesProforma::forBranch($user->branch_id)->where('status', 'draft')->count(),
            'sent' => SalesProforma::forBranch($user->branch_id)->where('status', 'sent')->count(),
            'accepted' => SalesProforma::forBranch($user->branch_id)->where('status', 'accepted')->count(),
            'rejected' => SalesProforma::forBranch($user->branch_id)->where('status', 'rejected')->count(),
            'expired' => SalesProforma::forBranch($user->branch_id)->where('status', 'expired')->count(),
        ];
        
        // Get recent proformas
        $recentProformas = SalesProforma::forBranch($user->branch_id)
            ->with('customer')
            ->latest('proforma_date')
            ->limit(10)
            ->get();
        
        return view('sales.index', compact('proformaStats', 'recentProformas'));
    }
    
    /**
     * Test authentication endpoint for debugging
     */
    public function testAuth()
    {
        return response()->json([
            'authenticated' => auth()->check(),
            'user' => auth()->user() ? auth()->user()->name : null,
            'branch_id' => auth()->user() ? auth()->user()->branch_id : null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
