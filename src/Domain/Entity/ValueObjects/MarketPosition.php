<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

use App\Domain\Entity\ValueObjects\Price;

class MarketPosition
{
    private Price $vehiclePrice;
    private Price $averageMarketPrice;
    private Price $lowestMarketPrice;
    private Price $highestMarketPrice;
    private float $percentageFromAverage;
    private string $position;

    public function __construct(
        Price $vehiclePrice,
        Price $averageMarketPrice,
        Price $lowestMarketPrice,
        Price $highestMarketPrice
    ) {
        $this->validatePrices($vehiclePrice, $averageMarketPrice, $lowestMarketPrice, $highestMarketPrice);

        $this->vehiclePrice = $vehiclePrice;
        $this->averageMarketPrice = $averageMarketPrice;
        $this->lowestMarketPrice = $lowestMarketPrice;
        $this->highestMarketPrice = $highestMarketPrice;

        $this->calculatePosition();
    }

    public function getVehiclePrice(): Price
    {
        return $this->vehiclePrice;
    }

    public function getAverageMarketPrice(): Price
    {
        return $this->averageMarketPrice;
    }

    public function getLowestMarketPrice(): Price
    {
        return $this->lowestMarketPrice;
    }

    public function getHighestMarketPrice(): Price
    {
        return $this->highestMarketPrice;
    }

    public function getPercentageFromAverage(): float
    {
        return $this->percentageFromAverage;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getPositionLabel(): string
    {
        return match ($this->position) {
            'very_low' => 'Muito Abaixo da Média',
            'low' => 'Abaixo da Média',
            'average' => 'Na Média do Mercado',
            'high' => 'Acima da Média',
            'very_high' => 'Muito Acima da Média',
            'excellent_deal' => 'Excelente Negócio',
            'overpriced' => 'Sobrepreçado',
            default => 'Posição Indefinida'
        };
    }

    public function getPriceDifferenceFromAverage(): Price
    {
        return $this->vehiclePrice->subtract($this->averageMarketPrice);
    }

    public function getPriceDifferenceFromLowest(): Price
    {
        return $this->vehiclePrice->subtract($this->lowestMarketPrice);
    }

    public function getPriceDifferenceFromHighest(): Price
    {
        return $this->vehiclePrice->subtract($this->highestMarketPrice);
    }

    public function isGoodDeal(): bool
    {
        return in_array($this->position, ['very_low', 'low', 'excellent_deal'], true);
    }

    public function isFairPrice(): bool
    {
        return $this->position === 'average';
    }

    public function isExpensive(): bool
    {
        return in_array($this->position, ['high', 'very_high', 'overpriced'], true);
    }

    public function getRecommendation(): string
    {
        return match ($this->position) {
            'excellent_deal' => 'Excelente oportunidade! Preço muito abaixo do mercado.',
            'very_low' => 'Ótimo negócio! Preço bem abaixo da média de mercado.',
            'low' => 'Bom negócio! Preço abaixo da média de mercado.',
            'average' => 'Preço justo, dentro da média de mercado.',
            'high' => 'Preço um pouco acima da média. Considere negociar.',
            'very_high' => 'Preço bem acima da média. Negocie ou procure outras opções.',
            'overpriced' => 'Preço muito alto para o mercado. Não recomendado.',
            default => 'Análise de preço não disponível.'
        };
    }

    public function getNegotiationSuggestion(): string
    {
        return match ($this->position) {
            'excellent_deal', 'very_low' => 'Aceite rapidamente, é um excelente preço!',
            'low' => 'Preço bom, pode tentar uma pequena redução.',
            'average' => 'Tente negociar um desconto de 5-10%.',
            'high' => 'Negocie uma redução de 10-15%.',
            'very_high' => 'Peça desconto significativo (15-25%) ou procure outras opções.',
            'overpriced' => 'Evite ou exija desconto de pelo menos 25%.',
            default => 'Analise melhor antes de decidir.'
        };
    }

    public function getMarketRange(): array
    {
        return [
            'min' => $this->lowestMarketPrice,
            'max' => $this->highestMarketPrice,
            'average' => $this->averageMarketPrice,
            'spread' => $this->highestMarketPrice->subtract($this->lowestMarketPrice)
        ];
    }

    public function getPositionInRange(): float
    {
        // Retorna onde o preço está no range (0 = mínimo, 1 = máximo)
        $range = $this->highestMarketPrice->subtract($this->lowestMarketPrice);
        if ($range->getValue() === 0.0) {
            return 0.5; // Todos os preços são iguais
        }

        $position = $this->vehiclePrice->subtract($this->lowestMarketPrice);
        return $position->getValue() / $range->getValue();
    }

    public function isWithinMarketRange(): bool
    {
        return $this->vehiclePrice->getValue() >= $this->lowestMarketPrice->getValue() &&
            $this->vehiclePrice->getValue() <= $this->highestMarketPrice->getValue();
    }

    public function getConfidenceLevel(): string
    {
        $position = $this->getPositionInRange();

        return match (true) {
            $position >= 0.2 && $position <= 0.8 => 'high',    // Dentro da faixa normal
            $position >= 0.1 && $position <= 0.9 => 'medium',  // Próximo dos extremos
            default => 'low'  // Fora do range normal
        };
    }

    private function validatePrices(
        Price $vehiclePrice,
        Price $averageMarketPrice,
        Price $lowestMarketPrice,
        Price $highestMarketPrice
    ): void {
        // Todos os preços devem ter a mesma moeda
        if (
            $vehiclePrice->getCurrency() !== $averageMarketPrice->getCurrency() ||
            $vehiclePrice->getCurrency() !== $lowestMarketPrice->getCurrency() ||
            $vehiclePrice->getCurrency() !== $highestMarketPrice->getCurrency()
        ) {
            throw new \InvalidArgumentException('All prices must use the same currency');
        }

        // Preços devem ser positivos
        if (
            $vehiclePrice->getValue() <= 0 ||
            $averageMarketPrice->getValue() <= 0 ||
            $lowestMarketPrice->getValue() <= 0 ||
            $highestMarketPrice->getValue() <= 0
        ) {
            throw new \InvalidArgumentException('All prices must be positive');
        }

        // Validação da lógica de preços
        if ($lowestMarketPrice->getValue() > $highestMarketPrice->getValue()) {
            throw new \InvalidArgumentException('Lowest price cannot be higher than highest price');
        }

        if (
            $averageMarketPrice->getValue() < $lowestMarketPrice->getValue() ||
            $averageMarketPrice->getValue() > $highestMarketPrice->getValue()
        ) {
            throw new \InvalidArgumentException('Average price must be between lowest and highest prices');
        }
    }

    private function calculatePosition(): void
    {
        $vehicleAmount = $this->vehiclePrice->getValue();
        $averageAmount = $this->averageMarketPrice->getValue();

        // Calcula percentual em relação à média
        $this->percentageFromAverage = (($vehicleAmount - $averageAmount) / $averageAmount) * 100;

        // Determina a posição no mercado
        $this->position = match (true) {
            $this->percentageFromAverage <= -30 => 'excellent_deal',
            $this->percentageFromAverage <= -15 => 'very_low',
            $this->percentageFromAverage <= -5 => 'low',
            $this->percentageFromAverage >= 30 => 'overpriced',
            $this->percentageFromAverage >= 15 => 'very_high',
            $this->percentageFromAverage >= 5 => 'high',
            default => 'average'
        };
    }

    public function equals(MarketPosition $other): bool
    {
        return $this->vehiclePrice->equals($other->vehiclePrice) &&
            $this->averageMarketPrice->equals($other->averageMarketPrice) &&
            $this->lowestMarketPrice->equals($other->lowestMarketPrice) &&
            $this->highestMarketPrice->equals($other->highestMarketPrice);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: %s (%+.1f%% da média)',
            $this->getPositionLabel(),
            $this->vehiclePrice->getFormattedValue(),
            $this->percentageFromAverage
        );
    }
}
