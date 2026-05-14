<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Services\RentalEventEquipment\RentalApprovalService;
use App\Models\RentalEventEquipment\RentalApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class RentalApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(RentalApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Approve a rental document
     */
    public function approve(Request $request, string $type, string $encodedId)
    {
        $request->validate([
            'level' => 'required|integer|min:1',
            'comments' => 'nullable|string|max:1000',
        ]);

        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        if (!$id) {
            return back()->with('error', 'Invalid document ID.');
        }

        // Get the document model
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) {
            return back()->with('error', 'Invalid document type.');
        }

        $document = $modelClass::findOrFail($id);

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin') || $user->hasRole('Super Admin') || ($user->is_admin ?? false);

        // Check if user can approve (super admin bypasses this check)
        if (!$isSuperAdmin && !$this->approvalService->canUserApprove($document, Auth::id(), $request->level)) {
            return back()->with('error', 'You do not have permission to approve this document.');
        }

        $success = $this->approvalService->approveDocument(
            $document,
            $request->level,
            Auth::id(),
            $request->comments
        );

        if ($success) {
            return back()->with('success', 'Document approved successfully.');
        }

        return back()->with('error', 'Failed to approve document.');
    }

    /**
     * Reject a rental document
     */
    public function reject(Request $request, string $type, string $encodedId)
    {
        $request->validate([
            'level' => 'required|integer|min:1',
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        if (!$id) {
            return back()->with('error', 'Invalid document ID.');
        }

        // Get the document model
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) {
            return back()->with('error', 'Invalid document type.');
        }

        $document = $modelClass::findOrFail($id);

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin') || $user->hasRole('Super Admin') || ($user->is_admin ?? false);

        // Check if user can approve (super admin bypasses this check)
        if (!$isSuperAdmin && !$this->approvalService->canUserApprove($document, Auth::id(), $request->level)) {
            return back()->with('error', 'You do not have permission to reject this document.');
        }

        $success = $this->approvalService->rejectDocument(
            $document,
            $request->level,
            Auth::id(),
            $request->rejection_reason
        );

        if ($success) {
            return back()->with('success', 'Document rejected successfully.');
        }

        return back()->with('error', 'Failed to reject document.');
    }

    /**
     * Get model class from type string
     */
    protected function getModelClass(string $type): ?string
    {
        $models = [
            'quotation' => \App\Models\RentalEventEquipment\RentalQuotation::class,
            'contract' => \App\Models\RentalEventEquipment\RentalContract::class,
            'deposit' => \App\Models\RentalEventEquipment\CustomerDeposit::class,
            'dispatch' => \App\Models\RentalEventEquipment\RentalDispatch::class,
            'return' => \App\Models\RentalEventEquipment\RentalReturn::class,
            'damage-charge' => \App\Models\RentalEventEquipment\RentalDamageCharge::class,
            'invoice' => \App\Models\RentalEventEquipment\RentalInvoice::class,
        ];

        return $models[$type] ?? null;
    }
}
