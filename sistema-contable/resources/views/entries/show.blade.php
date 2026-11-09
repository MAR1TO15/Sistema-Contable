<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asiento {{ $entry->number }} - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('entries.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Asientos contables</a>

        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="mb-1 text-2xl font-bold text-gray-900 dark:text-white">
                    Asiento No. {{ $entry->number }}
                    @if ($entry->is_closing)
                        <span class="ml-2 rounded bg-purple-100 px-2 py-1 align-middle text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-300">Asiento de cierre</span>
                    @endif
                </h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $entry->description }}</p>
            </div>

            @can('update', $entry)
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('entries.edit', $entry) }}" class="rounded-lg border border-blue-700 px-4 py-2 font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-gray-800">Editar</a>
                    <form method="POST" action="{{ route('entries.destroy', $entry) }}"
                        onsubmit="return confirm('¿Eliminar el asiento No. {{ $entry->number }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-700 px-4 py-2 font-medium text-red-700 hover:bg-red-50 dark:border-red-400 dark:text-red-400 dark:hover:bg-gray-800">Eliminar</button>
                    </form>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Este asiento pertenece a un periodo cerrado y no se puede modificar.</p>
            @endcan
        </div>

        <dl class="mb-6 grid grid-cols-1 gap-3 rounded-lg bg-white p-4 text-sm shadow sm:grid-cols-3 dark:bg-gray-800">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Fecha</dt>
                <dd class="text-gray-900 dark:text-white">{{ $entry->date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Periodo</dt>
                <dd class="text-gray-900 dark:text-white">{{ $entry->fiscalPeriod->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Registrado por</dt>
                <dd class="text-gray-900 dark:text-white">{{ $entry->user->name }}</dd>
            </div>
        </dl>

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3">Detalle</th>
                        <th class="px-4 py-3 text-right">Débito</th>
                        <th class="px-4 py-3 text-right">Crédito</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entry->lines as $line)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-3">
                                <span class="font-mono">{{ $line->account->code }}</span> {{ $line->account->name }}
                            </td>
                            <td class="px-4 py-3">{{ $line->description ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono">
                                @if (\App\Support\Money::toCents($line->debit) > 0)
                                    {{ \App\Support\Money::format(\App\Support\Money::toCents($line->debit)) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                @if (\App\Support\Money::toCents($line->credit) > 0)
                                    {{ \App\Support\Money::format(\App\Support\Money::toCents($line->credit)) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-300 font-semibold text-gray-900 dark:border-gray-600 dark:text-white">
                    <tr>
                        <td class="px-4 py-3" colspan="2">Totales</td>
                        <td class="px-4 py-3 text-right font-mono">{{ \App\Support\Money::format($totalDebit) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ \App\Support\Money::format($totalCredit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</body>
</html>
