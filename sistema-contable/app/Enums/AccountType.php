<?php

namespace App\Enums;

enum AccountType: string
{
    case Activo = 'activo';
    case Pasivo = 'pasivo';
    case Patrimonio = 'patrimonio';
    case Ingreso = 'ingreso';
    case Gasto = 'gasto';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Pasivo => 'Pasivo',
            self::Patrimonio => 'Patrimonio',
            self::Ingreso => 'Ingreso',
            self::Gasto => 'Gasto',
        };
    }

    /**
     * The nature an account of this type normally has. Contra accounts
     * (e.g. accumulated depreciation) may override it.
     */
    public function defaultNature(): AccountNature
    {
        return match ($this) {
            self::Activo, self::Gasto => AccountNature::Deudora,
            self::Pasivo, self::Patrimonio, self::Ingreso => AccountNature::Acreedora,
        };
    }
}
