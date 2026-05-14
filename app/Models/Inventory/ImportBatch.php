<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use App\Models\User;

class ImportBatch extends Model
{
    protected $table = 'inventory_import_batches';

    protected $fillable = [
        'company_id',
        'user_id',
        'file_name',
        'total_rows',
        'imported_rows',
        'error_rows',
        'status',
        'error_log',
        'job_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    // Methods
    public function markAsProcessing()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted($importedRows, $errorRows = 0, $errorLog = null)
    {
        $this->update([
            'status' => 'completed',
            'imported_rows' => $importedRows,
            'error_rows' => $errorRows,
            'error_log' => $errorLog,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($errorLog)
    {
        $this->update([
            'status' => 'failed',
            'error_log' => $errorLog,
            'completed_at' => now(),
        ]);
    }
}
