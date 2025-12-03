<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetDashboardDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // All authenticated users can access dashboard data
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'nullable|integer|min:1|max:12',
            'comparison' => 'nullable|in:MOM,YOY',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If year is provided but month is not, default to current month
        if ($this->has('year') && !$this->has('month')) {
            $this->merge(['month' => date('n')]);
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'year.integer' => 'Year must be a valid number.',
            'year.min' => 'Year must be 2020 or later.',
            'year.max' => 'Year cannot be more than one year in the future.',
            'month.integer' => 'Month must be a valid number.',
            'month.min' => 'Month must be between 1 and 12.',
            'month.max' => 'Month must be between 1 and 12.',
            'comparison.in' => 'Comparison type must be either MOM or YOY.',
        ];
    }
}
