<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

enum TransmissionType: string
{
    case MANUAL = 'manual';
    case AUTOMATIC = 'automatic';
    case CVT = 'cvt'; // Continuously Variable Transmission
    case AUTOMATED_MANUAL = 'automated_manual'; // Automatizada/Robotizada
    case DUAL_CLUTCH = 'dual_clutch'; // Dupla Embreagem (DSG, PDK, etc.)
    case SEQUENTIAL = 'sequential';

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::AUTOMATIC => 'Automática',
            self::CVT => 'CVT (Transmissão Continuamente Variável)',
            self::AUTOMATED_MANUAL => 'Automatizada/Robotizada',
            self::DUAL_CLUTCH => 'Dupla Embreagem (DSG)',
            self::SEQUENTIAL => 'Sequencial'
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::AUTOMATIC => 'Automática',
            self::CVT => 'CVT',
            self::AUTOMATED_MANUAL => 'Automatizada',
            self::DUAL_CLUTCH => 'DSG',
            self::SEQUENTIAL => 'Sequencial'
        };
    }

    public function hasClutchPedal(): bool
    {
        return match ($this) {
            self::MANUAL, self::SEQUENTIAL => true,
            default => false
        };
    }

    public function isAutomatic(): bool
    {
        return match ($this) {
            self::MANUAL => false,
            default => true
        };
    }

    public function getFuelEfficiency(): string
    {
        return match ($this) {
            self::MANUAL => 'high',
            self::CVT => 'very_high',
            self::DUAL_CLUTCH => 'high',
            self::AUTOMATIC => 'medium',
            self::AUTOMATED_MANUAL => 'medium',
            self::SEQUENTIAL => 'low'
        };
    }

    public function getComfortLevel(): string
    {
        return match ($this) {
            self::AUTOMATIC, self::CVT => 'excellent',
            self::DUAL_CLUTCH => 'very_good',
            self::AUTOMATED_MANUAL => 'good',
            self::MANUAL => 'moderate',
            self::SEQUENTIAL => 'low'
        };
    }

    public function getPerformanceLevel(): string
    {
        return match ($this) {
            self::DUAL_CLUTCH, self::SEQUENTIAL => 'excellent',
            self::MANUAL => 'very_good',
            self::AUTOMATED_MANUAL => 'good',
            self::AUTOMATIC => 'moderate',
            self::CVT => 'low'
        };
    }

    public function getMaintenanceCost(): string
    {
        return match ($this) {
            self::MANUAL => 'low',
            self::AUTOMATED_MANUAL => 'medium',
            self::AUTOMATIC, self::CVT => 'high',
            self::DUAL_CLUTCH, self::SEQUENTIAL => 'very_high'
        };
    }

    public function getTypicalGearCount(): string
    {
        return match ($this) {
            self::MANUAL => '5-6 marchas',
            self::AUTOMATIC => '4-10 marchas',
            self::CVT => 'Infinitas relações',
            self::AUTOMATED_MANUAL => '5-6 marchas',
            self::DUAL_CLUTCH => '6-8 marchas',
            self::SEQUENTIAL => '6-7 marchas'
        };
    }

    public function isGoodForTraffic(): bool
    {
        return match ($this) {
            self::AUTOMATIC, self::CVT => true,
            self::DUAL_CLUTCH, self::AUTOMATED_MANUAL => true,
            self::MANUAL, self::SEQUENTIAL => false
        };
    }

    public function isGoodForSport(): bool
    {
        return match ($this) {
            self::DUAL_CLUTCH, self::SEQUENTIAL, self::MANUAL => true,
            self::AUTOMATED_MANUAL => true,
            self::AUTOMATIC => false,
            self::CVT => false
        };
    }

    public function getLearningDifficulty(): string
    {
        return match ($this) {
            self::AUTOMATIC, self::CVT => 'very_easy',
            self::DUAL_CLUTCH => 'easy',
            self::AUTOMATED_MANUAL => 'moderate',
            self::MANUAL => 'moderate_to_hard',
            self::SEQUENTIAL => 'hard'
        };
    }

    public function getMarketPreference(): float
    {
        // Percentual de preferência no mercado brasileiro (aproximado)
        return match ($this) {
            self::AUTOMATIC => 0.45, // 45%
            self::MANUAL => 0.35,    // 35%
            self::CVT => 0.15,       // 15%
            self::AUTOMATED_MANUAL => 0.03, // 3%
            self::DUAL_CLUTCH => 0.02,      // 2%
            self::SEQUENTIAL => 0.001       // 0.1%
        };
    }

    public function getAdvantages(): array
    {
        return match ($this) {
            self::MANUAL => [
                'Menor custo de aquisição',
                'Menor custo de manutenção',
                'Maior economia de combustível',
                'Maior controle do condutor'
            ],
            self::AUTOMATIC => [
                'Maior conforto em trânsito',
                'Facilidade de dirigir',
                'Menor fadiga do motorista',
                'Ideal para cidade'
            ],
            self::CVT => [
                'Máxima economia de combustível',
                'Transições suaves',
                'Boa performance urbana',
                'Menor emissão de poluentes'
            ],
            self::AUTOMATED_MANUAL => [
                'Custo menor que automática',
                'Facilidade de dirigir',
                'Boa economia de combustível'
            ],
            self::DUAL_CLUTCH => [
                'Trocas muito rápidas',
                'Excelente performance',
                'Boa economia',
                'Tecnologia avançada'
            ],
            self::SEQUENTIAL => [
                'Máxima performance',
                'Controle total das trocas',
                'Uso esportivo/competição'
            ]
        };
    }

    public static function getUrbanChoices(): array
    {
        return [
            self::AUTOMATIC,
            self::CVT,
            self::DUAL_CLUTCH
        ];
    }

    public static function getEconomicalChoices(): array
    {
        return [
            self::MANUAL,
            self::CVT,
            self::DUAL_CLUTCH
        ];
    }

    public static function getSportChoices(): array
    {
        return [
            self::MANUAL,
            self::DUAL_CLUTCH,
            self::SEQUENTIAL
        ];
    }

    public static function fromString(string $transmission): self
    {
        return self::tryFrom(strtolower($transmission))
            ?? throw new \InvalidArgumentException("Invalid transmission type: {$transmission}");
    }
}
