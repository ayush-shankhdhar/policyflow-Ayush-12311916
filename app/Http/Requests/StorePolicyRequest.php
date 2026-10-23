<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolicyRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'client_request_id' => 'required|string',
            'agent_id' => 'required|string',
            'customer_name' => 'required|string',
            'date_of_birth' => 'required|date',
            'destination' => ['required', Rule::in(['DOMESTIC', 'INTERNATIONAL'])],
            'trip_start' => 'required|date',
            'trip_end' => 'required|date|after_or_equal:trip_start',
            'plan_code' => ['required', Rule::in(['BASIC', 'PLUS', 'PREMIUM'])],
            'sum_insured' => ['required', 'integer', Rule::in([100000, 300000, 500000])],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'trip_end.after_or_equal' => 'The trip_end must be on or after trip_start.',
        ];
    }
}
?>
