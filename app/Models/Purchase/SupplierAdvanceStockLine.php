<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAdvanceStockLine extends Model
{
    public const TYPE_ZILIZOUZWA = 'zilizouzwa';

    public const TYPE_ZIZONUNULIWA = 'zizonunuliwa';

    public const TYPE_BAKI = 'baki';

    protected $fillable = [
        'stock_record_id',
        'transaction_type',
        'idadi',
        'thamani',
    ];

    protected $casts = [
        'thamani' => 'decimal:2',
    ];

    public static function transactionTypeOptions(): array
    {
        return [
            self::TYPE_ZILIZOUZWA => 'Zilizouzwa',
            self::TYPE_ZIZONUNULIWA => 'Zizonunuliwa',
            self::TYPE_BAKI => 'Baki',
        ];
    }

    public static function orderedTypes(): array
    {
        return [
            self::TYPE_ZILIZOUZWA,
            self::TYPE_ZIZONUNULIWA,
            self::TYPE_BAKI,
        ];
    }

    public function transactionTypeLabel(): string
    {
        return self::transactionTypeOptions()[$this->transaction_type] ?? $this->transaction_type;
    }

    public function stockRecord(): BelongsTo
    {
        return $this->belongsTo(SupplierAdvanceStockRecord::class, 'stock_record_id');
    }
}
