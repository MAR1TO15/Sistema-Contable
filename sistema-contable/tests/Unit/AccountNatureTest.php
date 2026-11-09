<?php

namespace Tests\Unit;

use App\Enums\AccountNature;
use PHPUnit\Framework\TestCase;

class AccountNatureTest extends TestCase
{
    public function test_el_saldo_de_una_cuenta_deudora_es_debitos_menos_creditos(): void
    {
        $this->assertSame(700, AccountNature::Deudora->balance(debit: 1000, credit: 300));
        $this->assertSame(-200, AccountNature::Deudora->balance(debit: 100, credit: 300));
    }

    public function test_el_saldo_de_una_cuenta_acreedora_es_creditos_menos_debitos(): void
    {
        $this->assertSame(700, AccountNature::Acreedora->balance(debit: 300, credit: 1000));
        $this->assertSame(-200, AccountNature::Acreedora->balance(debit: 300, credit: 100));
    }
}
