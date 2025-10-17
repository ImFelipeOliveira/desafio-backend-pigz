<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class YearMonth
{
    private int $year;
    private int $month;

    public function __construct(int $year, int $month)
    {
        $this->validateYear($year);
        $this->validateMonth($month);

        $this->year = $year;
        $this->month = $month;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getMonthName(): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];

        return $months[$this->month];
    }

    public function getShortMonthName(): string
    {
        $months = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez'
        ];

        return $months[$this->month];
    }

    public function format(string $format = 'MM/yyyy'): string
    {
        return match ($format) {
            'MM/yyyy' => sprintf('%02d/%04d', $this->month, $this->year),
            'yyyy-MM' => sprintf('%04d-%02d', $this->year, $this->month),
            'MMM/yyyy' => $this->getShortMonthName() . '/' . $this->year,
            'MMMM yyyy' => $this->getMonthName() . ' ' . $this->year,
            'yyyy/MM' => sprintf('%04d/%02d', $this->year, $this->month),
            default => sprintf('%02d/%04d', $this->month, $this->year)
        };
    }

    public function toDateTime(): \DateTime
    {
        return new \DateTime(sprintf('%04d-%02d-01', $this->year, $this->month));
    }

    public function getFirstDay(): \DateTime
    {
        return new \DateTime(sprintf('%04d-%02d-01', $this->year, $this->month));
    }

    public function getLastDay(): \DateTime
    {
        $lastDay = date('t', mktime(0, 0, 0, $this->month, 1, $this->year));
        return new \DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $lastDay));
    }

    public function addMonths(int $months): self
    {
        $date = $this->toDateTime();
        $date->modify("+{$months} months");

        return new self((int) $date->format('Y'), (int) $date->format('n'));
    }

    public function subtractMonths(int $months): self
    {
        $date = $this->toDateTime();
        $date->modify("-{$months} months");

        return new self((int) $date->format('Y'), (int) $date->format('n'));
    }

    public function getNext(): self
    {
        return $this->addMonths(1);
    }

    public function getPrevious(): self
    {
        return $this->subtractMonths(1);
    }

    public function isBefore(YearMonth $other): bool
    {
        return $this->year < $other->year ||
            ($this->year === $other->year && $this->month < $other->month);
    }

    public function isAfter(YearMonth $other): bool
    {
        return $this->year > $other->year ||
            ($this->year === $other->year && $this->month > $other->month);
    }

    public function equals(YearMonth $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }

    public function differenceInMonths(YearMonth $other): int
    {
        return ($this->year - $other->year) * 12 + ($this->month - $other->month);
    }

    public function isCurrentMonth(): bool
    {
        $now = new \DateTime();
        return $this->year === (int) $now->format('Y') &&
            $this->month === (int) $now->format('n');
    }

    public function isFutureMonth(): bool
    {
        $current = self::current();
        return $this->isAfter($current);
    }

    public function isPastMonth(): bool
    {
        $current = self::current();
        return $this->isBefore($current);
    }

    // Aliases for compatibility
    public function isCurrent(): bool
    {
        return $this->isCurrentMonth();
    }

    public function isPast(): bool
    {
        return $this->isPastMonth();
    }

    public function isFuture(): bool
    {
        return $this->isFutureMonth();
    }

    public function diffInMonths(YearMonth $other): int
    {
        return $this->differenceInMonths($other);
    }

    public function getQuarter(): int
    {
        return (int) ceil($this->month / 3);
    }

    public function getQuarterName(): string
    {
        return 'Q' . $this->getQuarter() . ' ' . $this->year;
    }

    public function getSemester(): int
    {
        return $this->month <= 6 ? 1 : 2;
    }

    public function getSemesterName(): string
    {
        return ($this->getSemester() === 1 ? '1º' : '2º') . ' Semestre ' . $this->year;
    }

    public function getDaysInMonth(): int
    {
        return (int) date('t', mktime(0, 0, 0, $this->month, 1, $this->year));
    }

    public function isLeapYear(): bool
    {
        return date('L', mktime(0, 0, 0, 1, 1, $this->year)) === '1';
    }

    public static function current(): self
    {
        $now = new \DateTime();
        return new self((int) $now->format('Y'), (int) $now->format('n'));
    }

    public static function fromString(string $yearMonth): self
    {
        // Suporta formatos: "2024-03", "03/2024", "2024/03", "Mar/2024"
        $yearMonth = trim($yearMonth);

        // Formato ISO: 2024-03
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $yearMonth, $matches)) {
            return new self((int) $matches[1], (int) $matches[2]);
        }

        // Formato brasileiro: 03/2024
        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $yearMonth, $matches)) {
            return new self((int) $matches[2], (int) $matches[1]);
        }

        // Formato com barra invertida: 2024/03
        if (preg_match('/^(\d{4})\/(\d{1,2})$/', $yearMonth, $matches)) {
            return new self((int) $matches[1], (int) $matches[2]);
        }

        // Formato com nome do mês: Mar/2024, Março/2024
        $monthNames = [
            'jan' => 1,
            'janeiro' => 1,
            'fev' => 2,
            'fevereiro' => 2,
            'mar' => 3,
            'março' => 3,
            'abr' => 4,
            'abril' => 4,
            'mai' => 5,
            'maio' => 5,
            'jun' => 6,
            'junho' => 6,
            'jul' => 7,
            'julho' => 7,
            'ago' => 8,
            'agosto' => 8,
            'set' => 9,
            'setembro' => 9,
            'out' => 10,
            'outubro' => 10,
            'nov' => 11,
            'novembro' => 11,
            'dez' => 12,
            'dezembro' => 12,
        ];

        if (preg_match('/^(\w+)\/(\d{4})$/', $yearMonth, $matches)) {
            $monthName = strtolower($matches[1]);
            if (isset($monthNames[$monthName])) {
                return new self((int) $matches[2], $monthNames[$monthName]);
            }
        }

        throw new \InvalidArgumentException("Invalid year-month format: {$yearMonth}");
    }

    public static function fromDateTime(\DateTime $dateTime): self
    {
        return new self((int) $dateTime->format('Y'), (int) $dateTime->format('n'));
    }

    private function validateYear(int $year): void
    {
        if ($year < 1900 || $year > 2100) {
            throw new \InvalidArgumentException('Year must be between 1900 and 2100');
        }
    }

    private function validateMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Month must be between 1 and 12');
        }
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
