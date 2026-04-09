<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Category;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allTypes = array_map(
            static fn (DocumentType $type): string => $type->value,
            DocumentType::cases(),
        );

        shuffle($allTypes);

        $splitPoint = fake()->numberBetween(1, count($allTypes) - 1);
        $required = array_slice($allTypes, 0, $splitPoint);
        $optional = array_slice($allTypes, $splitPoint);

        $allDataFields = ['safety_text', 'warning_text', 'age_grading', 'material_information', 'usage_restrictions', 'safety_instructions', 'additional_notes'];
        shuffle($allDataFields);
        $requiredDataFields = array_slice($allDataFields, 0, fake()->numberBetween(0, count($allDataFields)));

        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'required_document_types' => $required,
            'optional_document_types' => $optional,
            'required_data_fields' => $requiredDataFields,
        ];
    }
}
