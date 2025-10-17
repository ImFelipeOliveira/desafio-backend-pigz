<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

enum FuelType: string
{
    case GASOLINE = 'gasoline';
    case ETHANOL = 'ethanol';
    case FLEX = 'flex';
    case DIESEL = 'diesel';
    case HYBRID = 'hybrid';
    case ELECTRIC = 'electric';
    case CNG = 'cng'; // Gás Natural Veicular
    case LPG = 'lpg'; // Gás Liquefeito de Petróleo

    public function getLabel(): string
    {
        return match ($this) {
            self::GASOLINE => 'Gasolina',
            self::ETHANOL => 'Etanol',
            self::FLEX => 'Flex (Gasolina/Etanol)',
            self::DIESEL => 'Diesel',
            self::HYBRID => 'Híbrido',
            self::ELECTRIC => 'Elétrico',
            self::CNG => 'GNV (Gás Natural Veicular)',
            self::LPG => 'GLP (Gás Liquefeito de Petróleo)'
        };
    }

    public function isRenewable(): bool
    {
        return match ($this) {
            self::ETHANOL, self::ELECTRIC, self::HYBRID => true,
            self::FLEX => true, // Pode usar etanol
            default => false
        };
    }

    public function isEcoFriendly(): bool
    {
        return match ($this) {
            self::ELECTRIC => true,
            self::HYBRID, self::ETHANOL => true,
            self::CNG => true, // Menos poluente que gasolina/diesel
            default => false
        };
    }

    public function getAverageConsumption(): float
    {
        // Consumo médio em km/l (valores aproximados)
        return match ($this) {
            self::GASOLINE => 12.0,
            self::ETHANOL => 8.5,
            self::FLEX => 11.0, // Média considerando uso misto
            self::DIESEL => 15.0,
            self::HYBRID => 18.0,
            self::ELECTRIC => 0.0, // Não se aplica (km/kWh seria outra métrica)
            self::CNG => 14.0,
            self::LPG => 10.0
        };
    }

    public function requiresSpecialInfrastructure(): bool
    {
        return match ($this) {
            self::ELECTRIC, self::CNG, self::LPG => true,
            default => false
        };
    }

    public function getEmissionLevel(): string
    {
        return match ($this) {
            self::ELECTRIC => 'zero',
            self::HYBRID, self::ETHANOL => 'low',
            self::CNG, self::FLEX => 'medium',
            self::GASOLINE, self::LPG => 'high',
            self::DIESEL => 'very_high'
        };
    }

    public function getCostEfficiency(): string
    {
        // Custo-benefício considerando preço do combustível e consumo
        return match ($this) {
            self::ELECTRIC => 'excellent',
            self::ETHANOL, self::CNG => 'very_good',
            self::FLEX, self::HYBRID => 'good',
            self::DIESEL => 'moderate',
            self::GASOLINE, self::LPG => 'poor'
        };
    }

    public function isAvailableInBrazil(): bool
    {
        return match ($this) {
            // Todos os tipos listados estão disponíveis no Brasil
            default => true
        };
    }

    public function getMainAdvantage(): string
    {
        return match ($this) {
            self::GASOLINE => 'Ampla rede de postos',
            self::ETHANOL => 'Combustível renovável nacional',
            self::FLEX => 'Flexibilidade na escolha do combustível',
            self::DIESEL => 'Alto torque e economia',
            self::HYBRID => 'Economia e baixa emissão',
            self::ELECTRIC => 'Zero emissão e baixo custo operacional',
            self::CNG => 'Baixo custo por km rodado',
            self::LPG => 'Menor custo que gasolina'
        };
    }

    public static function getPopularChoices(): array
    {
        return [
            self::FLEX,
            self::GASOLINE,
            self::DIESEL,
            self::HYBRID
        ];
    }

    public static function getEcologicalChoices(): array
    {
        return [
            self::ELECTRIC,
            self::HYBRID,
            self::ETHANOL,
            self::CNG
        ];
    }

    public static function fromString(string $fuelType): self
    {
        return self::tryFrom(strtolower($fuelType))
            ?? throw new \InvalidArgumentException("Invalid fuel type: {$fuelType}");
    }
}
