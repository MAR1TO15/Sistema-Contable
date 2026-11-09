<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{0: string|int|float|null, 1: int}>
     */
    public static function montos(): array
    {
        return [
            'entero' => ['1500', 150000],
            'un decimal' => ['0.5', 50],
            'dos decimales' => ['12.34', 1234],
            'vacio' => ['', 0],
            'nulo' => [null, 0],
            'decimal de la base de datos' => ['1500.00', 150000],
            'suma flotante de la base de datos' => [0.30000000000000004, 30],
            'flotante redondo' => [1500.0, 150000],
        ];
    }

    #[DataProvider('montos')]
    public function test_convierte_montos_a_centavos(string|int|float|null $monto, int $centavos): void
    {
        $this->assertSame($centavos, Money::toCents($monto));
    }

    public function test_convierte_centavos_a_decimal_para_la_base_de_datos(): void
    {
        $this->assertSame('1234.50', Money::fromCents(123450));
        $this->assertSame('0.05', Money::fromCents(5));
        $this->assertSame('0.00', Money::fromCents(0));
    }

    public function test_formatea_centavos_para_mostrar(): void
    {
        $this->assertSame('1,234,567.89', Money::format(123456789));
        $this->assertSame('0.30', Money::format(30));
    }

    public function test_formatea_montos_negativos(): void
    {
        $this->assertSame('-1,234.50', Money::format(-123450));
        $this->assertSame('-0.05', Money::format(-5));
    }
}
