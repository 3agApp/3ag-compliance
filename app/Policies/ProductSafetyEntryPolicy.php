<?php

namespace App\Policies;

use App\Models\Distributor;
use App\Models\ProductSafetyEntry;
use App\Models\User;
use Filament\Facades\Filament;

class ProductSafetyEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageSafetyEntries($user);
    }

    public function view(User $user, ProductSafetyEntry $productSafetyEntry): bool
    {
        return $this->canManageSafetyEntry($user, $productSafetyEntry);
    }

    public function create(User $user): bool
    {
        return $this->canManageSafetyEntries($user);
    }

    public function update(User $user, ProductSafetyEntry $productSafetyEntry): bool
    {
        return $this->canManageSafetyEntry($user, $productSafetyEntry);
    }

    public function delete(User $user, ProductSafetyEntry $productSafetyEntry): bool
    {
        return $this->canManageSafetyEntry($user, $productSafetyEntry);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageSafetyEntries($user);
    }

    private function canManageSafetyEntry(User $user, ProductSafetyEntry $productSafetyEntry): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        return $productSafetyEntry->product !== null
            && $user->canAccessProductInDistributor($tenant, $productSafetyEntry->product);
    }

    private function canManageSafetyEntries(User $user): bool
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
