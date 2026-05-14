<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'is_paid' => ['boolean'],
            'allow_half_day' => ['boolean'],
            'allow_hourly' => ['boolean'],
            'allow_negative' => ['boolean'],
            'min_duration_hours' => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'notice_days' => ['nullable', 'integer', 'min:0'],
            'doc_required_after_days' => ['nullable', 'integer', 'min:1'],
            'encashable' => ['boolean'],
            'carryover_cap_days' => ['nullable', 'integer', 'min:0'],
            'carryover_expiry_date' => ['nullable', 'date'],
            'annual_entitlement' => ['nullable', 'integer', 'min:0'],
            'accrual_type' => ['required', 'in:annual,monthly,none'],
            'is_active' => ['boolean'],
        ];
    }
}

