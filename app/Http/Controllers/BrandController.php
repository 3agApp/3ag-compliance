<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandStoreRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function store(BrandStoreRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->brands()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Brand added successfully.',
        ]);

        return back();
    }

    public function update(BrandUpdateRequest $request, Supplier $supplier, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Brand updated successfully.',
        ]);

        return back();
    }

    public function destroy(Supplier $supplier, Brand $brand): RedirectResponse
    {
        $brand->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Brand deleted successfully.',
        ]);

        return back();
    }
}
