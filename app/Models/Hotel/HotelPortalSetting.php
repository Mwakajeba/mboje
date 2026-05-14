<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPortalSetting extends Model
{
    protected $table = 'hotel_portal_settings';

    protected $fillable = [
        'company_id',
        'setting_key',
        'setting_value',
        'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public static function getSetting(int $companyId, string $key, ?string $default = null): ?string
    {
        $row = static::where('company_id', $companyId)
            ->where('setting_key', $key)
            ->first();

        return $row ? $row->setting_value : $default;
    }

    public static function setSetting(int $companyId, string $key, string $value, ?int $userId = null): void
    {
        static::updateOrCreate(
            ['company_id' => $companyId, 'setting_key' => $key],
            ['setting_value' => $value, 'updated_by' => $userId]
        );
    }
}
