<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ImprestDocument extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'imprest_request_id',
        'imprest_liquidation_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    // Relationships
    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class);
    }

    public function imprestLiquidation(): BelongsTo
    {
        return $this->belongsTo(ImprestLiquidation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Methods
    public function getFileUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $size = (int) $this->file_size;
        
        if ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        }
        
        return $size . ' bytes';
    }

    public function getDocumentTypeLabel(): string
    {
        return match($this->document_type) {
            'receipt' => 'Receipt',
            'invoice' => 'Invoice',
            'voucher' => 'Voucher',
            'supporting_document' => 'Supporting Document',
            'approval_form' => 'Approval Form',
            default => ucfirst(str_replace('_', ' ', $this->document_type))
        };
    }

    public function canBeDeleted(): bool
    {
        // Can only delete documents if the imprest request is not closed
        if ($this->imprest_request_id) {
            return $this->imprestRequest->status !== 'closed';
        }
        
        if ($this->imprest_liquidation_id) {
            return $this->imprestLiquidation->status !== 'approved';
        }
        
        return true;
    }
}