<?php

namespace Tests\Unit;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use PHPUnit\Framework\TestCase;

class AccountTypeTest extends TestCase
{
    public function test_activo_y_gasto_son_de_naturaleza_deudora(): void
    {
        $this->assertSame(AccountNature::Deudora, AccountType::Activo->defaultNature());
        $this->assertSame(AccountNature::Deudora, AccountType::Gasto->defaultNature());
    }

    public function test_pasivo_patrimonio_e_ingreso_son_de_naturaleza_acreedora(): void
    {
        $this->assertSame(AccountNature::Acreedora, AccountType::Pasivo->defaultNature());
        $this->assertSame(AccountNature::Acreedora, AccountType::Patrimonio->defaultNature());
        $this->assertSame(AccountNature::Acreedora, AccountType::Ingreso->defaultNature());
    }
}
