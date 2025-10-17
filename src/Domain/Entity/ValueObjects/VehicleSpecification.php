<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class VehicleSpecification
{
    private string $brand;
    private string $model;
    private ?string $version;
    private string $category;

    public function __construct(string $brand, string $model, string $category, ?string $version = null)
    {
        $this->validateBrand($brand);
        $this->validateModel($model);
        $this->validateCategory($category);

        $this->brand = $brand;
        $this->model = $model;
        $this->category = $category;
        $this->version = $version;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getFullName(): string
    {
        $fullName = $this->brand . ' ' . $this->model;

        if ($this->version) {
            $fullName .= ' ' . $this->version;
        }

        return $fullName;
    }

    public function equals(VehicleSpecification $other): bool
    {
        return $this->brand === $other->brand
            && $this->model === $other->model
            && $this->version === $other->version
            && $this->category === $other->category;
    }

    private function validateBrand(string $brand): void
    {
        if (empty(trim($brand))) {
            throw new \InvalidArgumentException('Brand cannot be empty');
        }

        if (strlen($brand) > 50) {
            throw new \InvalidArgumentException('Brand cannot exceed 50 characters');
        }
    }

    private function validateModel(string $model): void
    {
        if (empty(trim($model))) {
            throw new \InvalidArgumentException('Model cannot be empty');
        }

        if (strlen($model) > 50) {
            throw new \InvalidArgumentException('Model cannot exceed 50 characters');
        }
    }

    private function validateCategory(string $category): void
    {
        $allowedCategories = ['SUV', 'Sedan', 'Hatch', 'Pickup', 'Coupe', 'Convertible', 'Wagon', 'Van', 'Motorcycle'];

        if (!in_array($category, $allowedCategories)) {
            throw new \InvalidArgumentException('Invalid vehicle category: ' . $category);
        }
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
