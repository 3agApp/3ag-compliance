<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'optional_document_types' => is_array($this->optional_document_types) ? $this->optional_document_types : [],
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
            'optional_document_types' => ['present', 'array'],
            'optional_document_types.*' => ['string', Rule::in(array_column(DocumentType::cases(), 'value'))],
            'required_data_fields' => ['present', 'array'],
            'required_data_fields.*' => ['string', Rule::in([
                'safety_text', 'warning_text', 'age_grading',
                'material_information', 'usage_restrictions',
                'safety_instructions', 'additional_notes',
            ])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $required = $this->input('required_document_types', []);
            $optional = $this->input('optional_document_types', []);
            $overlap = array_intersect($required, $optional);

            if (count($overlap) > 0) {
                $validator->errors()->add(
                    'optional_document_types',
                    'A document type cannot be both required and optional.',
                );
            }
        }];
    }
}
