<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Supplier = 'supplier';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Supplier => 'Supplier',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Admin => 'warning',
            self::Supplier => 'info',
        };
    }

    public function canManageMembers(): bool
    {
        return $this !== self::Supplier;
    }

    public function canManageDistributor(): bool
    {
        return $this !== self::Supplier;
    }

    public function canAccessProducts(): bool
    {
        return true;
    }

    public function canEditProductDetails(): bool
    {
        return $this !== self::Supplier;
    }

    public function canSubmitProducts(): bool
    {
        return $this !== self::Supplier;
    }

    public function canDeleteDistributor(): bool
    {
        return $this === self::Owner;
    }
}
