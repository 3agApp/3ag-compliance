<?php

namespace App\Policies;

use App\Models\Distributor;
use App\Models\ProductComponent;
use App\Models\User;
use Filament\Facades\Filament;

class ProductComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageComponents($user);
    }

    public function view(User $user, ProductComponent $productComponent): bool
    {
        return $this->canManageComponent($user, $productComponent);
    }

    public function create(User $user): bool
    {
        return $this->canManageComponents($user);
    }

    public function update(User $user, ProductComponent $productComponent): bool
    {
        return $this->canManageComponent($user, $productComponent);
    }

    public function delete(User $user, ProductComponent $productComponent): bool
    {
        return $this->canManageComponent($user, $productComponent);
    }

    public function restore(User $user, ProductComponent $productComponent): bool
    {
        return $this->canManageComponent($user, $productComponent);
    }

    public function forceDelete(User $user, ProductComponent $productComponent): bool
    {
        return $this->canManageComponent($user, $productComponent);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageComponents($user);
    }

    private function canManageComponent(User $user, ProductComponent $productComponent): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        return $user->canAccessProductInDistributor($tenant, $productComponent->product);
    }

    private function canManageComponents(User $user): bool
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
}
