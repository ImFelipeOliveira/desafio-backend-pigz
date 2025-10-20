<?php

declare(strict_types=1);

namespace App\Infra\Persistence\Doctrine\Entity;

use App\Domain\Entity\FipeEntry;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\YearMonth;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'fipe_entries')]
#[ORM\HasLifecycleCallbacks]
class FipeEntryEntity
{
  #[ORM\Id]
  #[ORM\Column(type: 'guid')]
  private string $id;

  #[ORM\Column(length: 50)]
  private string $fipeCode;

  #[ORM\Column(length: 50)]
  private string $brand;

  #[ORM\Column(length: 50)]
  private string $model;

  #[ORM\Column(length: 50, nullable: true)]
  private ?string $version = null;

  #[ORM\Column(length: 30)]
  private string $category;

  #[ORM\Column(length: 20)]
  private string $fuelType;

  #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
  private float $priceValue;

  #[ORM\Column(length: 3)]
  private string $priceCurrency;

  #[ORM\Column(length: 7)]
  private string $referenceMonth; // Format: YYYY-MM

  #[ORM\Column(type: 'integer')]
  private int $modelYear;

  #[ORM\Column(type: 'datetime_immutable')]
  private \DateTimeImmutable $createdAt;

  #[ORM\Column(type: 'datetime_immutable', nullable: true)]
  private ?\DateTimeImmutable $updatedAt;

  public function __construct()
  {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = null;
  }

  public static function fromDomain(FipeEntry $fipe): self
  {
    $entity = new self();
    $entity->id = $fipe->getId()->toRfc4122();
    $entity->fipeCode = $fipe->getFipeCode()->getCode();

    $spec = $fipe->getSpecification();
    $entity->brand = $spec->getBrand();
    $entity->model = $spec->getModel();
    $entity->version = $spec->getVersion();
    $entity->category = $spec->getCategory();

    $entity->fuelType = $fipe->getFuelType()->value;

    $price = $fipe->getPrice();
    $entity->priceValue = $price->getValue();
    $entity->priceCurrency = $price->getCurrency();

    $entity->referenceMonth = $fipe->getReferenceMonth()->format();
    $entity->modelYear = $fipe->getModelYear();

    $entity->createdAt = $fipe->getCreatedAt();
    $entity->updatedAt = $fipe->getUpdatedAt();

    return $entity;
  }

  public function toDomain(): FipeEntry
  {
    $fipe = new FipeEntry(
      new FipeCode($this->fipeCode),
      new VehicleSpecification(
        $this->brand,
        $this->model,
        $this->category,
        $this->version
      ),
      FuelType::from($this->fuelType),
      new Price($this->priceValue, $this->priceCurrency),
      YearMonth::fromString($this->referenceMonth),
      $this->modelYear
    );

    $reflection = new \ReflectionClass($fipe);

    $idProp = $reflection->getProperty('id');
    $idProp->setAccessible(true);
    $idProp->setValue($fipe, Uuid::fromString($this->id));

    $createdProp = $reflection->getProperty('createdAt');
    $createdProp->setAccessible(true);
    $createdProp->setValue($fipe, $this->createdAt);

    $updatedProp = $reflection->getProperty('updatedAt');
    $updatedProp->setAccessible(true);
    $updatedProp->setValue($fipe, $this->updatedAt);

    return $fipe;
  }

  public function update(self $newData): void
  {
    $this->priceValue = $newData->priceValue;
    $this->priceCurrency = $newData->priceCurrency;
    $this->referenceMonth = $newData->referenceMonth;
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): string
  {
    return $this->id;
  }

  public function getFipeCode(): string
  {
    return $this->fipeCode;
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

  public function getFuelType(): string
  {
    return $this->fuelType;
  }

  public function getPriceValue(): float
  {
    return $this->priceValue;
  }

  public function getPriceCurrency(): string
  {
    return $this->priceCurrency;
  }

  public function getReferenceMonth(): string
  {
    return $this->referenceMonth;
  }

  public function getModelYear(): int
  {
    return $this->modelYear;
  }

  public function getCreatedAt(): \DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function getUpdatedAt(): ?\DateTimeImmutable
  {
    return $this->updatedAt;
  }

  #[ORM\PreUpdate]
  public function onPreUpdate(): void
  {
    $this->updatedAt = new \DateTimeImmutable();
  }
}
