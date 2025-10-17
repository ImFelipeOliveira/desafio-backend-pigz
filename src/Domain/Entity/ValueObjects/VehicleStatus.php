<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

enum VehicleStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponível',
            self::RESERVED => 'Reservado',
            self::SOLD => 'Vendido',
            self::MAINTENANCE => 'Manutenção',
            self::INACTIVE => 'Inativo'
        };
    }

    public function canBeReserved(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function canBeSold(): bool
    {
        return in_array($this, [self::AVAILABLE, self::RESERVED]);
    }

    public function isActive(): bool
    {
        return $this !== self::INACTIVE;
    }
}
