<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class VIN
{
    private string $number;

    public function __construct(string $number)
    {
        $this->validateVIN($number);
        $this->number = strtoupper($number);
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getCountryCode(): string
    {
        // Primeiro caractere indica o país/região
        $firstChar = $this->number[0];

        return match ($firstChar) {
            '1', '4', '5' => 'USA',
            '2' => 'Canada',
            '3' => 'Mexico',
            '9' => 'Brazil',
            'S', 'Z' => 'Europe',
            'J' => 'Japan',
            'K' => 'Korea',
            'L' => 'China',
            default => 'Unknown'
        };
    }

    public function getManufacturerCode(): string
    {
        // Primeiros 3 caracteres representam o fabricante
        return substr($this->number, 0, 3);
    }

    public function getModelYear(): ?int
    {
        // 10º caractere representa o ano do modelo
        $yearChar = $this->number[9];

        // Mapeamento de caracteres para anos
        $yearMapping = [
            'A' => 2010,
            'B' => 2011,
            'C' => 2012,
            'D' => 2013,
            'E' => 2014,
            'F' => 2015,
            'G' => 2016,
            'H' => 2017,
            'J' => 2018,
            'K' => 2019,
            'L' => 2020,
            'M' => 2021,
            'N' => 2022,
            'P' => 2023,
            'R' => 2024,
            'S' => 2025,
            'T' => 2026,
            'V' => 2027,
            'W' => 2028,
            'X' => 2029,
            'Y' => 2030,
            '1' => 2001,
            '2' => 2002,
            '3' => 2003,
            '4' => 2004,
            '5' => 2005,
            '6' => 2006,
            '7' => 2007,
            '8' => 2008,
            '9' => 2009
        ];

        return $yearMapping[$yearChar] ?? null;
    }

    public function getCheckDigit(): string
    {
        // 9º caractere é o dígito verificador
        return $this->number[8];
    }

    public function equals(VIN $other): bool
    {
        return $this->number === $other->number;
    }

    private function validateVIN(string $number): void
    {
        // Remove espaços e converte para maiúsculo
        $cleanNumber = strtoupper(str_replace(' ', '', $number));

        // VIN deve ter exatamente 17 caracteres
        if (strlen($cleanNumber) !== 17) {
            throw new \InvalidArgumentException('VIN must be exactly 17 characters long');
        }

        // VIN não pode conter I, O ou Q (para evitar confusão com 1, 0)
        if (preg_match('/[IOQ]/', $cleanNumber)) {
            throw new \InvalidArgumentException('VIN cannot contain letters I, O, or Q');
        }

        // VIN deve conter apenas letras e números
        if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $cleanNumber)) {
            throw new \InvalidArgumentException('VIN contains invalid characters');
        }

        // Validação básica do dígito verificador (simplificada)
        if (!$this->isValidCheckDigit($cleanNumber)) {
            throw new \InvalidArgumentException('Invalid VIN check digit');
        }
    }

    private function isValidCheckDigit(string $vin): bool
    {
        // Implementação simplificada da validação do dígito verificador
        $weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
        $transliterations = [
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6,
            'G' => 7,
            'H' => 8,
            'J' => 1,
            'K' => 2,
            'L' => 3,
            'M' => 4,
            'N' => 5,
            'P' => 7,
            'R' => 9,
            'S' => 2,
            'T' => 3,
            'U' => 4,
            'V' => 5,
            'W' => 6,
            'X' => 7,
            'Y' => 8,
            'Z' => 9
        ];

        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $char = $vin[$i];
            if (is_numeric($char)) {
                $value = (int) $char;
            } else {
                $value = $transliterations[$char] ?? 0;
            }
            $sum += $value * $weights[$i];
        }

        $remainder = $sum % 11;
        $checkDigit = $remainder < 10 ? (string) $remainder : 'X';

        return $checkDigit === $vin[8];
    }

    public function __toString(): string
    {
        return $this->number;
    }
}
