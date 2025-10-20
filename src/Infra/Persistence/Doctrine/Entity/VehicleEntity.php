<?php

declare(strict_types=1);

namespace App\Infra\Persistence\Doctrine\Entity;

use App\Domain\Entity\ValueObjects\Mileage;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleStatus;
use App\Domain\Entity\ValueObjects\VIN;
use App\Domain\Entity\ValueObjects\Year;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\TransmissionType;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\Vehicle;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'vehicles')]
#[ORM\HasLifecycleCallbacks]
class VehicleEntity
{
  #[ORM\Id]
  #[ORM\Column(type: 'guid')]
  private string $id;

  // Base specification pieces mirror the VehicleSpecification value object.
  #[ORM\Column(length: 50)]
  private string $brand;

  #[ORM\Column(length: 50)]
  private string $model;

  #[ORM\Column(length: 50, nullable: true)]
  private ?string $version = null;

  #[ORM\Column(length: 30)]
  private string $category;

  #[ORM\Column(type: 'integer')]
  private int $year;

  #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
  private float $priceValue;

  #[ORM\Column(length: 3)]
  private string $priceCurrency;

  #[ORM\Column(length: 20)]
  private string $status;

  #[ORM\Column(type: 'integer')]
  private int $mileage;

  #[ORM\Column(length: 17, nullable: true)]
  private ?string $vin = null;

  #[ORM\Column(length: 20)]
  private string $fuelType;

  #[ORM\Column(length: 20)]
  private string $transmission;

  #[ORM\Column(length: 50)]
  private string $fipeCode;

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $description = null;

  #[ORM\Column(type: 'json')]
  private array $images = [];

  #[ORM\Column(length: 36)]
  private string $ownerId;

  #[ORM\Column(type: 'datetime_immutable')]
  private \DateTimeImmutable $createdAt;

  #[ORM\Column(type: 'datetime_immutable')]
  private \DateTimeImmutable $updatedAt;

  public function __construct(?string $id = null)
  {
    $this->id = $id ?? Uuid::v7()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): string
  {
    return $this->id;
  }

  public function setId(string $id): self
  {
    $this->id = $id;

    return $this;
  }

  public function getBrand(): string
  {
    return $this->brand;
  }

  public function setBrand(string $brand): self
  {
    $this->brand = $brand;

    return $this;
  }

  public function getModel(): string
  {
    return $this->model;
  }

  public function setModel(string $model): self
  {
    $this->model = $model;

    return $this;
  }

  public function getVersion(): ?string
  {
    return $this->version;
  }

  public function setVersion(?string $version): self
  {
    $this->version = $version;

    return $this;
  }

  public function getCategory(): string
  {
    return $this->category;
  }

  public function setCategory(string $category): self
  {
    $this->category = $category;

    return $this;
  }

  public function getYear(): int
  {
    return $this->year;
  }

  public function setYear(int $year): self
  {
    $this->year = $year;

    return $this;
  }

  public function getPriceValue(): float
  {
    return $this->priceValue;
  }

  public function setPriceValue(float $priceValue): self
  {
    $this->priceValue = $priceValue;

    return $this;
  }

  public function getPriceCurrency(): string
  {
    return $this->priceCurrency;
  }

  public function setPriceCurrency(string $priceCurrency): self
  {
    $this->priceCurrency = $priceCurrency;

    return $this;
  }

  public function getStatus(): string
  {
    return $this->status;
  }

  public function setStatus(string $status): self
  {
    $this->status = $status;

    return $this;
  }

  public function getMileage(): int
  {
    return $this->mileage;
  }

  public function setMileage(int $mileage): self
  {
    $this->mileage = $mileage;

    return $this;
  }

  public function getVin(): ?string
  {
    return $this->vin;
  }

  public function setVin(?string $vin): self
  {
    $this->vin = $vin;

    return $this;
  }

  public function getFuelType(): string
  {
    return $this->fuelType;
  }

  public function setFuelType(string $fuelType): self
  {
    $this->fuelType = $fuelType;

    return $this;
  }

  public function getTransmission(): string
  {
    return $this->transmission;
  }

  public function setTransmission(string $transmission): self
  {
    $this->transmission = $transmission;

    return $this;
  }

  public function getFipeCode(): string
  {
    return $this->fipeCode;
  }

  public function setFipeCode(string $fipeCode): self
  {
    $this->fipeCode = $fipeCode;

    return $this;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(?string $description): self
  {
    $this->description = $description;

    return $this;
  }

  public function getImages(): array
  {
    return $this->images;
  }

  public function setImages(array $images): self
  {
    $this->images = $images;

    return $this;
  }

  public function getOwnerId(): string
  {
    return $this->ownerId;
  }

  public function setOwnerId(string $ownerId): self
  {
    $this->ownerId = $ownerId;

    return $this;
  }

  public function getCreatedAt(): \DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function setCreatedAt(\DateTimeImmutable $createdAt): self
  {
    $this->createdAt = $createdAt;

    return $this;
  }

  public function getUpdatedAt(): \DateTimeImmutable
  {
    return $this->updatedAt;
  }

  public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
  {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  #[ORM\PrePersist]
  public function onPrePersist(): void
  {
    $now = new \DateTimeImmutable();
    $this->createdAt = $this->createdAt ?? $now;
    $this->updatedAt = $this->updatedAt ?? $now;
  }

  #[ORM\PreUpdate]
  public function onPreUpdate(): void
  {
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function updateFrom(self $entity): void
  {
    $this->brand = $entity->getBrand();
    $this->model = $entity->getModel();
    $this->version = $entity->getVersion();
    $this->category = $entity->getCategory();
    $this->year = $entity->getYear();
    $this->priceValue = $entity->getPriceValue();
    $this->priceCurrency = $entity->getPriceCurrency();
    $this->status = $entity->getStatus();
    $this->mileage = $entity->getMileage();
    $this->vin = $entity->getVin();
    $this->fuelType = $entity->getFuelType();
    $this->transmission = $entity->getTransmission();
    $this->fipeCode = $entity->getFipeCode();
    $this->description = $entity->getDescription();
    $this->images = $entity->getImages();
    $this->updatedAt = new \DateTimeImmutable();
  }

  public static function fromDomain(Vehicle $vehicle): self
  {
    $entity = new self($vehicle->getId());
    $entity->setBrand($vehicle->getSpecification()->getBrand());
    $entity->setModel($vehicle->getSpecification()->getModel());
    $entity->setVersion($vehicle->getSpecification()->getVersion());
    $entity->setCategory($vehicle->getSpecification()->getCategory());
    $entity->setYear($vehicle->getYear()->getValue());
    $entity->setPriceValue($vehicle->getPrice()->getValue());
    $entity->setPriceCurrency($vehicle->getPrice()->getCurrency());
    $entity->setStatus($vehicle->getStatus()->value);
    $entity->setMileage($vehicle->getMileage()->getKilometers());
    $entity->setVin($vehicle->getVin()?->getNumber());
    $entity->setFuelType($vehicle->getFuelType()->value);
    $entity->setTransmission($vehicle->getTransmission()->value);
    $entity->setFipeCode($vehicle->getFipeCode()->getCode());
    $entity->setDescription($vehicle->getDescription());
    $entity->setImages($vehicle->getImages());
    $entity->setOwnerId($vehicle->getOwnerId());
    $entity->createdAt = $vehicle->getCreatedAt();
    $entity->updatedAt = $vehicle->getUpdatedAt();
    return $entity;
  }

  public function toDomain(): Vehicle
  {
    return Vehicle::restore(
      $this->id,
      new \App\Domain\Entity\ValueObjects\VehicleSpecification(
        $this->brand,
        $this->model,
        $this->category,
        $this->version
      ),
      new Year($this->year),
      new Price($this->priceValue, $this->priceCurrency),
      VehicleStatus::from($this->status),
      new Mileage($this->mileage),
      $this->vin ? new VIN($this->vin) : null,
      FuelType::from($this->fuelType),
      TransmissionType::from($this->transmission),
      new FipeCode($this->fipeCode),
      $this->description,
      $this->images,
      $this->ownerId,
      $this->createdAt,
      $this->updatedAt
    );
  }
}
