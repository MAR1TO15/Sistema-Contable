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
}
