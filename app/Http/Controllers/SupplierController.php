<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierStoreRequest;
use App\Http\Requests\SupplierUpdateRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('suppliers/index', [
            'suppliers' => Supplier::query()
                ->orderBy('id', 'desc')
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('suppliers/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierStoreRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Supplier created successfully.',
        ]);

        return to_route('suppliers.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): Response
    {
        return Inertia::render('suppliers/show', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('suppliers/edit', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierUpdateRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Supplier updated successfully.',
        ]);

        return to_route('suppliers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Supplier deleted successfully.',
        ]);

        return to_route('suppliers.index');
    }
}
