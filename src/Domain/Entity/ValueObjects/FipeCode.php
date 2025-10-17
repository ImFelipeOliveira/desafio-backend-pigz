<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class FipeCode
{
    private string $code;
    private string $vehicleType;

    public function __construct(string $code, string $vehicleType = 'car')
    {
        $this->validateCode($code);
        $this->validateVehicleType($vehicleType);

        $this->code = $code;
        $this->vehicleType = strtolower($vehicleType);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getVehicleType(): string
    {
        return $this->vehicleType;
    }

    public function getBrandCode(): string
    {
        // Para carros: primeiros 3 dígitos representam a marca
        // Para motos: primeiros 2 dígitos
        // Para caminhões: primeiros 3 dígitos
        return match ($this->vehicleType) {
            'motorcycle' => substr($this->code, 0, 2),
            default => substr($this->code, 0, 3)
        };
    }

    public function getModelCode(): string
    {
        // Código completo sem o último dígito (que pode ser ano/versão)
        return substr($this->code, 0, -1);
    }

    public function getVersionCode(): string
    {
        // Último dígito ou últimos dígitos específicos da versão
        return substr($this->code, -1);
    }

    public function isValidForVehicleType(string $vehicleType): bool
    {
        $vehicleType = strtolower($vehicleType);

        return match ($vehicleType) {
            'car' => $this->vehicleType === 'car' && $this->isValidCarCode(),
            'motorcycle' => $this->vehicleType === 'motorcycle' && $this->isValidMotorcycleCode(),
            'truck' => $this->vehicleType === 'truck' && $this->isValidTruckCode(),
            default => false
        };
    }

    public function getApiEndpoint(): string
    {
        // Endpoints da API FIPE
        return match ($this->vehicleType) {
            'car' => 'carros',
            'motorcycle' => 'motos',
            'truck' => 'caminhoes',
            default => 'carros'
        };
    }

    public function getVehicleTypeLabel(): string
    {
        return match ($this->vehicleType) {
            'car' => 'Automóvel',
            'motorcycle' => 'Motocicleta',
            'truck' => 'Caminhão/Ônibus',
            default => 'Veículo'
        };
    }

    public function formatForDisplay(): string
    {
        // Formata o código para exibição (com separadores)
        return match ($this->vehicleType) {
            'motorcycle' => substr($this->code, 0, 2) . '-' . substr($this->code, 2),
            default => substr($this->code, 0, 3) . '-' . substr($this->code, 3)
        };
    }

    public function equals(FipeCode $other): bool
    {
        return $this->code === $other->code &&
            $this->vehicleType === $other->vehicleType;
    }

    public static function fromBrandAndModel(string $brandCode, string $modelCode, string $vehicleType = 'car'): self
    {
        $vehicleType = strtolower($vehicleType);

        $fullCode = match ($vehicleType) {
            'motorcycle' => str_pad($brandCode, 2, '0', STR_PAD_LEFT) .
                str_pad($modelCode, 3, '0', STR_PAD_LEFT),
            default => str_pad($brandCode, 3, '0', STR_PAD_LEFT) .
                str_pad($modelCode, 4, '0', STR_PAD_LEFT)
        };

        return new self($fullCode, $vehicleType);
    }

    public static function parseFromFipeString(string $fipeString): self
    {
        // Parse de strings como "001004-1" (carros) ou "01001-1" (motos)
        $cleaned = preg_replace('/[^0-9]/', '', $fipeString);

        // Determina o tipo baseado no comprimento
        $vehicleType = match (strlen($cleaned)) {
            5 => 'motorcycle',
            7 => 'car',
            default => 'car'
        };

        return new self($cleaned, $vehicleType);
    }

    private function validateCode(string $code): void
    {
        // Remove caracteres não numéricos para validação
        $numericCode = preg_replace('/[^0-9]/', '', $code);

        if (empty($numericCode)) {
            throw new \InvalidArgumentException('FIPE code cannot be empty');
        }

        // Código deve ter entre 5 e 8 dígitos
        if (strlen($numericCode) < 5 || strlen($numericCode) > 8) {
            throw new \InvalidArgumentException('FIPE code must be between 5 and 8 digits');
        }

        // Verifica se contém apenas números
        if (!ctype_digit($numericCode)) {
            throw new \InvalidArgumentException('FIPE code must contain only numbers');
        }
    }

    private function validateVehicleType(string $vehicleType): void
    {
        $validTypes = ['car', 'motorcycle', 'truck'];
        $vehicleType = strtolower($vehicleType);

        if (!in_array($vehicleType, $validTypes, true)) {
            throw new \InvalidArgumentException(
                'Vehicle type must be one of: ' . implode(', ', $validTypes)
            );
        }
    }

    private function isValidCarCode(): bool
    {
        // Carros: geralmente 6-7 dígitos
        return strlen($this->code) >= 6 && strlen($this->code) <= 7;
    }

    private function isValidMotorcycleCode(): bool
    {
        // Motos: geralmente 5 dígitos
        return strlen($this->code) === 5;
    }

    private function isValidTruckCode(): bool
    {
        // Caminhões: geralmente 6-7 dígitos
        return strlen($this->code) >= 6 && strlen($this->code) <= 7;
    }

    public function __toString(): string
    {
        return $this->formatForDisplay();
    }
}
