<?php

namespace App\Models\Sales;

use App\Helpers\AmountInWords;
use App\Models\Customer;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class SalesProforma extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'proforma_number',
        'customer_id',
        'proforma_date',
        'valid_until',
        'user_id',
        'status',
        'subtotal',
        'currency',
        'exchange_rate',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'tax_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'total_amount',
        'notes',
        'terms_conditions',
        'attachment',
        'branch_id',
        'company_id',
        'created_by'
    ];

    protected $casts = [
        'proforma_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
        'currency' => 'TZS',
        'exchange_rate' => 1.000000,
        'vat_type' => 'no_vat',
        'discount_type' => 'percentage'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesProformaItem::class, 'sales_proforma_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
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

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getFormattedDateAttribute()
    {
        return $this->proforma_date ? $this->proforma_date->format('M d, Y') : '';
    }

    public function getFormattedValidUntilAttribute()
    {
        return $this->valid_until ? $this->valid_until->format('M d, Y') : '';
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 2);
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }

    public function getCustomerNameAttribute()
    {
        return $this->customer ? $this->customer->name : 'N/A';
    }

    public function getStatusBadgeClassAttribute()
    {
        $colors = [
            'draft' => 'secondary',
            'sent' => 'info',
            'accepted' => 'success',
            'rejected' => 'danger',
            'expired' => 'warning'
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getStatusBadgeAttribute()
    {
        $color = $this->status_badge_class;
        return '<span class="badge bg-' . $color . '">' . strtoupper($this->status) . '</span>';
    }

    public function getActionsAttribute()
    {
        $actions = '<div class="btn-group" role="group">';
        $actions .= '<button type="button" class="btn btn-sm btn-outline-primary view-proforma" data-id="' . $this->id . '" title="View"><i class="bx bx-eye"></i></button>';
        $actions .= '<button type="button" class="btn btn-sm btn-outline-warning edit-proforma" data-id="' . $this->id . '" title="Edit"><i class="bx bx-edit"></i></button>';
        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-proforma" data-id="' . $this->id . '" data-name="' . $this->proforma_number . '" title="Delete"><i class="bx bx-trash"></i></button>';
        $actions .= '</div>';
        
        return $actions;
    }

    // Auto-generate proforma number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proforma) {
            if (empty($proforma->proforma_number)) {
                $year = date('Y');
                $month = date('m');
                $lastProforma = static::where('proforma_number', 'like', "PF-{$year}{$month}%")
                    ->orderBy('proforma_number', 'desc')
                    ->first();
                
                if ($lastProforma) {
                    $lastNumber = (int) substr($lastProforma->proforma_number, -4);
                    $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $newNumber = '0001';
                }
                
                $proforma->proforma_number = "PF-{$year}{$month}{$newNumber}";
            }

            // Set branch and company from auth user
            if (auth()->check()) {
                $proforma->branch_id = $proforma->branch_id ?? auth()->user()->branch_id;
                $proforma->company_id = $proforma->company_id ?? auth()->user()->company_id;
                $proforma->created_by = $proforma->created_by ?? auth()->id();
            }
        });
    }
}
