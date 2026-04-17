<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'distributor_id' => Distributor::factory(),
            'supplier_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::Admin,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'invited_by' => User::factory(),
        ];
    }

    public function supplierScoped(?Supplier $supplier = null): static
    {
        return $this->state(function (array $attributes) use ($supplier): array {
            $supplier ??= Supplier::factory()->create([
                'distributor_id' => $attributes['distributor_id'],
            ]);

            return [
                'role' => Role::Supplier,
                'supplier_id' => $supplier->id,
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_at' => now(),
        ]);
    }
}
