<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
        };
    }
}
