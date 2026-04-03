<?php

use App\Models\Supplier;
use Illuminate\Database\QueryException;

it('requires a supplier code and name', function () {
    expect(fn () => Supplier::create([
        'name' => 'Acme Supplies',
    ]))->toThrow(QueryException::class);

    expect(fn () => Supplier::create([
        'supplier_code' => 'SUP-001',
    ]))->toThrow(QueryException::class);
});

it('allows nullable supplier fields to be omitted', function () {
    $supplier = Supplier::create([
        'supplier_code' => 'SUP-001',
        'name' => 'Acme Supplies',
    ]);

    $this->assertModelExists($supplier);

    expect($supplier->address)->toBeNull()
        ->and($supplier->country)->toBeNull()
        ->and($supplier->email)->toBeNull()
        ->and($supplier->phone)->toBeNull()
        ->and($supplier->active)->toBeNull()
        ->and($supplier->kontor_id)->toBeNull();
});

it('casts the active flag to a boolean value', function () {
    $supplier = Supplier::create([
        'supplier_code' => 'SUP-002',
        'name' => 'Globex Corporation',
        'active' => 1,
    ]);

    expect($supplier->active)->toBeTrue();
});
