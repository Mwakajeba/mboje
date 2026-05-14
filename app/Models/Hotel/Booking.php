<?php

namespace App\Models\Hotel;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Helpers\HashIdHelper;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'booking_number',
        'room_id',
        'guest_id',
        'check_in',
        'check_out',
        'check_in_time',
        'check_out_time',
        'adults',
        'children',
        'nights',
        'room_rate',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'status',
        'payment_status',
        'booking_source',
        'special_requests',
        'notes',
        'cancellation_date',
        'cancellation_reason',
        'cancellation_fee',
        'branch_id',
        'company_id',
        'created_by'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'room_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'cancellation_date' => 'date'
    ];

    // Relationships
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(\App\Models\GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'hotel_booking');
    }

    public function paymentGlTransactions(): HasMany
    {
        return $this->hasMany(\App\Models\GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'hotel_payment');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(\App\Models\Receipt::class, 'reference_number', 'booking_number')
            ->where('reference_type', 'hotel_booking');
    }

    // Scopes
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('check_in', [$startDate, $endDate])
              ->orWhereBetween('check_out', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->where('check_in', '<=', $startDate)
                       ->where('check_out', '>=', $endDate);
              });
        });
    }

    public function scopeCurrent($query)
    {
        return $query->where('check_in', '<=', now())
                    ->where('check_out', '>=', now())
                    ->whereIn('status', ['confirmed', 'checked_in']);
    }

    // Accessors
    public function getTotalGuestsAttribute()
    {
        return $this->adults + $this->children;
    }

    public function getIsCurrentAttribute()
    {
        return $this->check_in <= now() && $this->check_out >= now() && 
               in_array($this->status, ['confirmed', 'checked_in']);
    }

    public function getIsOverdueAttribute()
    {
        return $this->check_out < now() && $this->status === 'checked_in';
    }

    public function getCanCheckInAttribute()
    {
        // Allow check-in if:
        // 1. Status is confirmed
        // 2. Not already checked in, checked out, or cancelled
        // 3. Check-in date is not more than 1 day in the future (allow early check-in)
        return $this->status === 'confirmed' && 
               !in_array($this->status, ['checked_in', 'checked_out', 'cancelled']) &&
               $this->check_in <= now()->addDay();
    }

    public function getCanCheckOutAttribute()
    {
        return $this->status === 'checked_in';
    }

    public function getCanCancelAttribute()
    {
        // Allow cancel for pending or confirmed bookings at any time before checked out
        return in_array($this->status, ['pending', 'confirmed']);
    }

    // Methods
    public function generateBookingNumber()
    {
        $datePrefix = now()->format('Ymd');
        $prefix = 'BK' . $datePrefix;
        
        // Find the highest sequence number for today's date
        // Exclude soft-deleted bookings to avoid conflicts
        $lastBooking = static::where('booking_number', 'like', $prefix . '%')
            ->orderBy('booking_number', 'desc')
            ->first();
        
        if ($lastBooking) {
            // Extract the sequence number from the last booking number
            $lastSequence = (int) substr($lastBooking->booking_number, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }
        
        $bookingNumber = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        
        // Double-check uniqueness (handle race conditions)
        $maxAttempts = 10;
        $attempts = 0;
        while (static::where('booking_number', $bookingNumber)->exists() && $attempts < $maxAttempts) {
            $nextSequence++;
            $bookingNumber = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        return $bookingNumber;
    }

    public function calculateTotalAmount()
    {
        return $this->room_rate * $this->nights;
    }

    public function calculateBalanceDue()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function checkIn()
    {
        if ($this->can_check_in) {
            $this->update([
                'status' => 'checked_in',
                'check_in_time' => now()
            ]);
            
            // Update room status to occupied
            $this->room->update(['status' => 'occupied']);
            
            return true;
        }
        return false;
    }

    public function checkOut()
    {
        if ($this->can_check_out) {
            $this->update([
                'status' => 'checked_out',
                'check_out_time' => now()
            ]);
            
            // Update room status back to available
            $this->room->update(['status' => 'available']);
            
            return true;
        }
        return false;
    }

    public function cancel($reason = null, $fee = 0)
    {
        if ($this->can_cancel) {
            \DB::transaction(function () use ($reason, $fee) {
                // Delete all receipts related to this booking
                $receipts = $this->receipts;
                foreach ($receipts as $receipt) {
                    // Delete GL transactions related to this receipt
                    $receipt->glTransactions()->delete();
                    // Delete receipt items
                    $receipt->receiptItems()->delete();
                    // Delete the receipt itself
                    $receipt->delete();
                }
                
                // Delete all GL transactions related to this booking
                $this->glTransactions()->delete();
                $this->paymentGlTransactions()->delete();
                
                // Reset payment information since receipts are deleted
                $this->update([
                    'status' => 'cancelled',
                    'cancellation_date' => now(),
                    'cancellation_reason' => $reason,
                    'cancellation_fee' => $fee,
                    'paid_amount' => 0,
                    'balance_due' => $this->total_amount,
                    'payment_status' => 'pending'
                ]);
                
                // If the booking was checked in, make room available again
                if ($this->status === 'checked_in') {
                    $this->room->update(['status' => 'available']);
                }

                // GL for cancellation fee (create receivable and income)
                if ($fee > 0) {
                    // Debit: Accounts Receivable (fee)
                    \App\Models\GlTransaction::create([
                        'chart_account_id' => $this->getAccountsReceivableAccountIdStatic(),
                        'amount' => $fee,
                        'nature' => 'debit',
                        'transaction_type' => 'hotel_cancellation',
                        'transaction_id' => $this->id,
                        'description' => "Cancellation fee for booking #{$this->booking_number}",
                        'date' => now(),
                        'branch_id' => $this->branch_id,
                        'user_id' => (\Auth::id() ?: $this->created_by)
                    ]);

                    // Credit: Cancellation/Penalty Income (use Penalty Income seeded id 100)
                    $incomeAccountId = $this->getCancellationFeeIncomeAccountIdStatic();
                    \App\Models\GlTransaction::create([
                        'chart_account_id' => $incomeAccountId,
                        'amount' => $fee,
                        'nature' => 'credit',
                        'transaction_type' => 'hotel_cancellation',
                        'transaction_id' => $this->id,
                        'description' => "Cancellation fee income - booking #{$this->booking_number}",
                        'date' => now(),
                        'branch_id' => $this->branch_id,
                        'user_id' => (\Auth::id() ?: $this->created_by)
                    ]);
                }
            });
            
            return true;
        }
        return false;
    }

    // Static helpers for GL accounts from model context
    private function getAccountsReceivableAccountIdStatic()
    {
        $account = \App\Models\ChartAccount::where('account_name', 'Trade Receivables')->value('id');
        if ($account) return (int) $account;
        // Fallback to seeded id 6 if present
        $byId = \App\Models\ChartAccount::where('id', 6)->value('id');
        if ($byId) return (int) $byId;
        // Create minimal AR account
        return \App\Models\ChartAccount::create([
            'account_name' => 'Accounts Receivable',
            'account_code' => '1200',
        ])->id;
    }

    private function getCancellationFeeIncomeAccountIdStatic()
    {
        // Prefer system setting if exists
        $fromSetting = \App\Models\SystemSetting::where('key', 'hotel_cancellation_fee_income_account_id')->value('value');
        if ($fromSetting) return (int) $fromSetting;
        // Fallback to seeded Penalty Income (id 100)
        $byId = \App\Models\ChartAccount::where('id', 100)->value('id');
        if ($byId) return (int) $byId;
        // Fallback to Other Income: Penalty Income by name
        $byName = \App\Models\ChartAccount::where('account_name', 'Penalty Income')->value('id');
        if ($byName) return (int) $byName;
        // Create if missing
        return \App\Models\ChartAccount::create([
            'account_name' => 'Penalty Income',
            'account_code' => '4105',
        ])->id;
    }

    public function addPayment($amount)
    {
        $newPaidAmount = $this->paid_amount + $amount;
        $newBalanceDue = $this->total_amount - $newPaidAmount;
        
        $paymentStatus = 'paid';
        if ($newBalanceDue > 0) {
            $paymentStatus = $newPaidAmount > 0 ? 'partial' : 'pending';
        }
        
        $this->update([
            'paid_amount' => $newPaidAmount,
            'balance_due' => $newBalanceDue,
            'payment_status' => $paymentStatus
        ]);
        
        return $this;
    }

    public function getDurationInDays()
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    public function getRemainingNights()
    {
        if ($this->status !== 'checked_in') return 0;
        
        $remaining = now()->diffInDays($this->check_out);
        return max(0, $remaining);
    }

    /**
     * Resolve the model instance for route model binding using hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is hash_id or null, decode the hash ID
        if ($field === 'hash_id' || $field === null) {
            $id = HashIdHelper::decode($value);
            if ($id !== null) {
                return $this->findOrFail($id);
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKey()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Get the route key name for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Get guest name with avatar for DataTables
     */
    public function getGuestNameAttribute()
    {
        $firstName = $this->guest->first_name ?? '';
        $lastName = $this->guest->last_name ?? '';
        $email = $this->guest->email ?? 'No email';
        $initial = substr($firstName ?: 'G', 0, 1);
        
        return '
            <div class="d-flex align-items-center">
                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                    <span class="text-white fw-semibold">' . $initial . '</span>
                </div>
                <div>
                    <h6 class="mb-0">' . $firstName . ' ' . $lastName . '</h6>
                    <small class="text-muted">' . $email . '</small>
                </div>
            </div>
        ';
    }

    /**
     * Get room info for DataTables
     */
    public function getRoomInfoAttribute()
    {
        $roomNumber = $this->room->room_number ?? 'N/A';
        $roomType = ucfirst($this->room->room_type ?? 'N/A');
        
        return '
            <div>
                <span class="fw-semibold">' . $roomNumber . '</span>
                <br>
                <small class="text-muted">' . $roomType . '</small>
            </div>
        ';
    }

    /**
     * Get status badge for DataTables
     */
    public function getStatusBadgeAttribute()
    {
        $status = $this->status;
        $badgeClass = match($status) {
            'confirmed' => 'success',
            'pending' => 'warning',
            'online_booking' => 'purple', // Purple badge for online bookings
            'cancelled' => 'danger',
            'checked_in' => 'info',
            'checked_out' => 'secondary',
            default => 'info'
        };
        
        $displayStatus = ucfirst(str_replace('_', ' ', $status));
        
        // Special styling for online_booking
        if ($status === 'online_booking') {
            return '<span class="badge" style="background-color: #6f42c1;">' . $displayStatus . '</span>';
        }
        
        return '<span class="badge bg-' . $badgeClass . '">' . $displayStatus . '</span>';
    }

    /**
     * Get payment status badge for DataTables
     */
    public function getPaymentStatusBadgeAttribute()
    {
        $status = $this->payment_status ?? 'pending';
        $badgeClass = match($status) {
            'paid' => 'success',
            'partial' => 'warning',
            default => 'danger'
        };
        
        $displayStatus = ucfirst($status);
        
        return '<span class="badge bg-' . $badgeClass . '">' . $displayStatus . '</span>';
    }

    /**
     * Get actions for DataTables
     */
    public function getActionsAttribute()
    {
        $actions = '
            <div class="d-flex gap-1">
                <a href="' . route('bookings.show', $this) . '" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="bx bx-show"></i>
                </a>
        ';
        
        // Only show edit button if booking is not checked out
        if ($this->status !== 'checked_out') {
            $actions .= '
                <a href="' . route('bookings.edit', $this) . '" class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
            ';
        }

        if ($this->status === 'confirmed') {
            $actions .= '
                <button type="button" class="btn btn-sm btn-outline-success" title="Check In" onclick="confirmCheckInIndex(' . $this->id . ', \'' . route('bookings.check-in', $this) . '\')">
                    <i class="bx bx-log-in"></i>
                </button>
            ';
        }

        if ($this->status === 'checked_in') {
            $actions .= '
                <button type="button" class="btn btn-sm btn-outline-info" title="Check Out" onclick="confirmCheckOutIndex(' . $this->id . ', \'' . route('bookings.check-out', $this) . '\')">
                    <i class="bx bx-log-out"></i>
                </button>
            ';
        }

        // Accept button for online bookings
        if ($this->status === 'online_booking') {
            $actions .= '
                <button type="button" class="btn btn-sm btn-success" title="Accept Booking" onclick="confirmAcceptBooking(' . $this->id . ', \'' . route('bookings.accept', $this) . '\')">
                    <i class="bx bx-check"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Cancel Booking" onclick="confirmCancelIndex(' . $this->id . ', \'' . route('bookings.cancel', $this) . '\')">
                    <i class="bx bx-x"></i>
                </button>
            ';
        }

        if (in_array($this->status, ['pending', 'confirmed'])) {
            $actions .= '
                <button type="button" class="btn btn-sm btn-outline-danger" title="Cancel" onclick="confirmCancelIndex(' . $this->id . ', \'' . route('bookings.cancel', $this) . '\')">
                    <i class="bx bx-x"></i>
                </button>
            ';
        }

        if (($this->paid_amount ?? 0) <= 0) {
            $actions .= '
                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete Booking" onclick="confirmDeleteBookingIndex(' . $this->id . ', \'' . route('bookings.destroy', $this) . '\')">
                    <i class="bx bx-trash"></i>
                </button>
            ';
        }

        $actions .= '</div>';
        
        return $actions;
    }
}