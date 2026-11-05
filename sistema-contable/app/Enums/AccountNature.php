<?php

namespace App\Enums;

enum AccountNature: string
{
    case Deudora = 'deudora';
    case Acreedora = 'acreedora';

    public function label(): string
    {
        return match ($this) {
            self::Deudora => 'Deudora',
            self::Acreedora => 'Acreedora',
        };
    }

    /**
     * The balance of an account of this nature, in the same unit as the
     * given totals: debits minus credits for deudora, the reverse for acreedora.
     */
    public function balance(int $debit, int $credit): int
    {
        return match ($this) {
            self::Deudora => $debit - $credit,
            self::Acreedora => $credit - $debit,
        };
    }
}
