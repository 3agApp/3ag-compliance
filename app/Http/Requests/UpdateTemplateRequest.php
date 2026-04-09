<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'required_document_types' => is_array($this->required_document_types) ? $this->required_document_types : [],
            'required_data_fields' => is_array($this->required_data_fields) ? $this->required_data_fields : [],
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'required_document_types' => ['present', 'array'],
            'required_document_types.*' => ['string', Rule::in(array_column(DocumentType::cases(), 'value'))],
            'required_data_fields' => ['present', 'array'],
            'required_data_fields.*' => ['string', Rule::in([
                'safety_text', 'warning_text', 'age_grading',
                'material_information', 'usage_restrictions',
                'safety_instructions', 'additional_notes',
            ])],
        ];
    }
}
