<?php

namespace App\Policies;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewProducts($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canAccessProduct($user, $product);
    }

    public function create(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canAccessProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->canManageProducts($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    private function canViewProducts(User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        $role = $user->getRoleForDistributor($tenant);

        if (! $role?->canAccessProducts()) {
            return false;
        }

        if ($role->canManageDistributor()) {
            return true;
        }

        return filled($user->getSupplierIdForDistributor($tenant));
    }

    private function canManageProducts(User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        return $user->getRoleForDistributor($tenant)?->canManageDistributor() ?? false;
    }

    private function canAccessProduct(User $user, Product $product): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        return $user->canAccessProductInDistributor($tenant, $product);
    }
}
