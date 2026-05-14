<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequestRequest extends FormRequest
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
            'leave_type_id' => ['sometimes', 'required', 'exists:leave_types,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'reliever_id' => ['nullable', 'exists:hr_employees,id'],
            'segments' => ['sometimes', 'required', 'array', 'min:1'],
            'segments.*.start_at' => ['required_with:segments', 'date'],
            'segments.*.end_at' => ['required_with:segments', 'date', 'after_or_equal:segments.*.start_at'],
            'segments.*.granularity' => ['required_with:segments', 'in:full_day,half_day,hourly'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'leave_type_id.exists' => 'The selected leave type is invalid.',
            'segments.min' => 'Please specify at least one leave period.',
            'segments.*.end_at.after_or_equal' => 'End date must be after or equal to start date.',
            'attachments.*.mimes' => 'Attachments must be PDF, JPG, JPEG, PNG, DOC, or DOCX files.',
            'attachments.*.max' => 'Each attachment must not exceed 2MB.',
        ];
    }
}

