<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * A minimal chart of accounts a client can start from. Accounts that
 * have children are grouping accounts; the leaves are postable.
 */
class BaseAccountCatalog
{
    /**
     * Root code => [name, type, [group code => [name, [leaf code => name]]]].
     *
     * @var array<string, array{0: string, 1: AccountType, 2: array<string, array{0: string, 1: array<string, string>}>}>
     */
    private const CATALOG = [
        '1' => ['Activo', AccountType::Activo, [
            '11' => ['Activo corriente', [
                '1101' => 'Caja y bancos',
                '1102' => 'Cuentas por cobrar',
                '1103' => 'Inventarios',
            ]],
            '12' => ['Activo no corriente', [
                '1201' => 'Propiedad, planta y equipo',
            ]],
        ]],
        '2' => ['Pasivo', AccountType::Pasivo, [
            '21' => ['Pasivo corriente', [
                '2101' => 'Cuentas por pagar',
                '2102' => 'Impuestos por pagar',
            ]],
            '22' => ['Pasivo no corriente', [
                '2201' => 'Préstamos a largo plazo',
            ]],
        ]],
        '3' => ['Patrimonio', AccountType::Patrimonio, [
            '31' => ['Capital', [
                '3101' => 'Capital social',
                '3102' => 'Utilidades retenidas',
            ]],
        ]],
        '4' => ['Ingresos', AccountType::Ingreso, [
            '41' => ['Ingresos operacionales', [
                '4101' => 'Ventas',
                '4102' => 'Servicios prestados',
            ]],
        ]],
        '5' => ['Gastos', AccountType::Gasto, [
            '51' => ['Gastos operacionales', [
                '5101' => 'Sueldos y salarios',
                '5102' => 'Alquileres',
                '5103' => 'Servicios básicos',
            ]],
        ]],
    ];

    public function createFor(Client $client): void
    {
        DB::transaction(function () use ($client) {
            foreach (self::CATALOG as $code => [$name, $type, $groups]) {
                $root = $this->create($client, null, $code, $name, $type, postable: false);

                foreach ($groups as $groupCode => [$groupName, $leaves]) {
                    $group = $this->create($client, $root, $groupCode, $groupName, $type, postable: false);

                    foreach ($leaves as $leafCode => $leafName) {
                        $this->create($client, $group, $leafCode, $leafName, $type, postable: true);
                    }
                }
            }
        });
    }

    private function create(Client $client, ?Account $parent, string $code, string $name, AccountType $type, bool $postable): Account
    {
        return $client->accounts()->create([
            'parent_id' => $parent?->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'nature' => $type->defaultNature(),
            'is_postable' => $postable,
        ]);
    }
}
