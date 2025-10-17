<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\ValueObjects\MarketPosition;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\YearMonth;
use Symfony\Component\Uid\Uuid;

class VehicleComparison
{
    private Uuid $id;
    private Vehicle $targetVehicle;
    private array $comparableVehicles; // Vehicle[]
    private MarketPosition $marketPosition;
    private YearMonth $analysisMonth;
    private array $fipeEntries; // FipeEntry[]
    private string $userId;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        Vehicle $targetVehicle,
        string $userId,
        ?YearMonth $analysisMonth = null
    ) {
        $this->id = Uuid::v4();
        $this->targetVehicle = $targetVehicle;
        $this->userId = $userId;
        $this->analysisMonth = $analysisMonth ?? YearMonth::current();
        $this->comparableVehicles = [];
        $this->fipeEntries = [];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    // Getters
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTargetVehicle(): Vehicle
    {
        return $this->targetVehicle;
    }

    public function getComparableVehicles(): array
    {
        return $this->comparableVehicles;
    }

    public function getMarketPosition(): ?MarketPosition
    {
        return $this->marketPosition ?? null;
    }

    public function getAnalysisMonth(): YearMonth
    {
        return $this->analysisMonth;
    }

    public function getFipeEntries(): array
    {
        return $this->fipeEntries;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Business Methods
    public function addComparableVehicle(Vehicle $vehicle): void
    {
        // Verificar se o veículo pode ser comparado
        if (!$this->targetVehicle->canBeComparedWith($vehicle)) {
            throw new \DomainException('Vehicle cannot be compared with target vehicle');
        }

        // Evitar duplicatas
        foreach ($this->comparableVehicles as $existing) {
            if ($existing->getId() === $vehicle->getId()) {
                return; // Já existe
            }
        }

        $this->comparableVehicles[] = $vehicle;
        $this->markAsUpdated();
    }

    public function removeComparableVehicle(string $vehicleId): void
    {
        $this->comparableVehicles = array_filter(
            $this->comparableVehicles,
            fn(Vehicle $vehicle) => $vehicle->getId() !== $vehicleId
        );
        
        $this->comparableVehicles = array_values($this->comparableVehicles); // Reindex
        $this->markAsUpdated();
    }

    public function addFipeEntry(FipeEntry $fipeEntry): void
    {
        // Verificar se a entrada FIPE é relevante para a comparação
        if (!$fipeEntry->matchesVehicle($this->targetVehicle)) {
            throw new \DomainException('FIPE entry does not match target vehicle');
        }

        $this->fipeEntries[] = $fipeEntry;
        $this->markAsUpdated();
    }

    public function calculateMarketPosition(): MarketPosition
    {
        if (empty($this->comparableVehicles)) {
            throw new \DomainException('No comparable vehicles available for market analysis');
        }

        $prices = array_map(fn(Vehicle $v) => $v->getPrice(), $this->comparableVehicles);
        $prices[] = $this->targetVehicle->getPrice(); // Incluir o veículo alvo

        $priceValues = array_map(fn(Price $p) => $p->getValue(), $prices);
        
        $averagePrice = new Price(
            array_sum($priceValues) / count($priceValues),
            $this->targetVehicle->getPrice()->getCurrency()
        );
        
        $lowestPrice = new Price(
            min($priceValues),
            $this->targetVehicle->getPrice()->getCurrency()
        );
        
        $highestPrice = new Price(
            max($priceValues),
            $this->targetVehicle->getPrice()->getCurrency()
        );

        $this->marketPosition = new MarketPosition(
            $this->targetVehicle->getPrice(),
            $averagePrice,
            $lowestPrice,
            $highestPrice
        );

        $this->markAsUpdated();
        return $this->marketPosition;
    }

    public function getComparisonSummary(): array
    {
        return [
            'target_vehicle' => [
                'name' => $this->targetVehicle->getFullName(),
                'price' => $this->targetVehicle->getPrice()->getFormattedValue(),
                'characteristics' => $this->targetVehicle->getMainCharacteristics()
            ],
            'comparable_vehicles_count' => count($this->comparableVehicles),
            'fipe_entries_count' => count($this->fipeEntries),
            'market_position' => $this->marketPosition ? [
                'position' => $this->marketPosition->getPositionLabel(),
                'percentage_from_average' => $this->marketPosition->getPercentageFromAverage(),
                'recommendation' => $this->marketPosition->getRecommendation()
            ] : null,
            'analysis_month' => $this->analysisMonth->format('M/Y'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }

    public function getPriceRange(): array
    {
        if (empty($this->comparableVehicles)) {
            return [
                'min' => $this->targetVehicle->getPrice(),
                'max' => $this->targetVehicle->getPrice(),
                'average' => $this->targetVehicle->getPrice()
            ];
        }

        $allPrices = array_map(fn(Vehicle $v) => $v->getPrice(), $this->comparableVehicles);
        $allPrices[] = $this->targetVehicle->getPrice();

        $priceValues = array_map(fn(Price $p) => $p->getValue(), $allPrices);

        return [
            'min' => new Price(min($priceValues), $this->targetVehicle->getPrice()->getCurrency()),
            'max' => new Price(max($priceValues), $this->targetVehicle->getPrice()->getCurrency()),
            'average' => new Price(
                array_sum($priceValues) / count($priceValues),
                $this->targetVehicle->getPrice()->getCurrency()
            )
        ];
    }

    public function isAnalysisCurrent(): bool
    {
        return $this->analysisMonth->isCurrent();
    }

    public function needsUpdate(): bool
    {
        // Análise precisa ser atualizada se:
        // 1. Não é do mês atual
        // 2. Não tem posição de mercado calculada
        // 3. Não tem veículos comparáveis suficientes
        
        return !$this->analysisMonth->isCurrent() 
            || $this->marketPosition === null
            || count($this->comparableVehicles) < 3; // Mínimo 3 para comparação confiável
    }

    public function getRecommendation(): string
    {
        if ($this->marketPosition === null) {
            return 'Análise de mercado ainda não realizada.';
        }

        $confidence = count($this->comparableVehicles) >= 5 ? 'alta' : 'moderada';
        
        return sprintf(
            '%s (Confiança %s baseada em %d veículos comparáveis)',
            $this->marketPosition->getRecommendation(),
            $confidence,
            count($this->comparableVehicles)
        );
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            'Comparação: %s (%s) - %d veículos comparáveis',
            $this->targetVehicle->getFullName(),
            $this->analysisMonth->format('M/Y'),
            count($this->comparableVehicles)
        );
    }
}