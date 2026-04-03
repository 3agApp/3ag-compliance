<?php

use App\Models\Supplier;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays the suppliers index page', function () {
    Supplier::factory()->count(3)->create();

    $this->get(route('suppliers.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/index'));
});

it('redirects guests to login from suppliers index', function () {
    auth()->logout();

    $this->get(route('suppliers.index'))
        ->assertRedirect(route('login'));
});

it('displays the create supplier form', function () {
    $this->get(route('suppliers.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/create'));
});

it('stores a new supplier', function () {
    $this->post(route('suppliers.store'), [
        'supplier_code' => 'SUP-99999',
        'name' => 'Test Supplier',
        'email' => 'test@supplier.com',
        'phone' => '+1234567890',
        'address' => '123 Main St',
        'country' => 'Germany',
        'active' => true,
        'kontor_id' => 'KON-0001',
    ])->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', [
        'supplier_code' => 'SUP-99999',
        'name' => 'Test Supplier',
        'email' => 'test@supplier.com',
    ]);
});

it('validates required fields on store', function () {
    $this->post(route('suppliers.store'), [])
        ->assertSessionHasErrors(['supplier_code', 'name']);
});

it('validates email format on store', function () {
    $this->post(route('suppliers.store'), [
        'supplier_code' => 'SUP-001',
        'name' => 'Test',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors(['email']);
});

it('displays a single supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->get(route('suppliers.show', $supplier))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/show'));
});

it('displays the edit supplier form', function () {
    $supplier = Supplier::factory()->create();

    $this->get(route('suppliers.edit', $supplier))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/edit'));
});

it('updates an existing supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->put(route('suppliers.update', $supplier), [
        'supplier_code' => 'SUP-UPDATED',
        'name' => 'Updated Name',
        'email' => 'updated@supplier.com',
    ])->assertRedirect(route('suppliers.index'));

    expect($supplier->fresh())
        ->supplier_code->toBe('SUP-UPDATED')
        ->name->toBe('Updated Name')
        ->email->toBe('updated@supplier.com');
});

it('validates required fields on update', function () {
    $supplier = Supplier::factory()->create();

    $this->put(route('suppliers.update', $supplier), [])
        ->assertSessionHasErrors(['supplier_code', 'name']);
});

it('deletes a supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
});
