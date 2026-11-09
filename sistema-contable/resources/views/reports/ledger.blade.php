<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Libro mayor - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Libro mayor</h1>

        <form method="GET" action="{{ route('reports.ledger') }}" class="mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="account" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Cuenta</label>
                <select id="account" name="account" required
                    class="block rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Selecciona una cuenta</option>
                    @foreach ($accounts as $option)
                        <option value="{{ $option->id }}" @selected($account?->id === $option->id)>{{ $option->code }} — {{ $option->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="period" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Periodo</label>
                <select id="period" name="period"
                    class="block rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los periodos</option>
                    @foreach ($periods as $option)
                        <option value="{{ $option->id }}" @selected($period?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Consultar</button>
        </form>

        @if ($account)
            <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                <span class="font-mono">{{ $account->code }}</span> {{ $account->name }}
            </h2>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Naturaleza {{ strtolower($account->nature->label()) }}</p>

            <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Asiento</th>
                            <th class="px-4 py-3 text-right">Débito</th>
                            <th class="px-4 py-3 text-right">Crédito</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($period)
                            <tr class="border-t border-gray-200 italic dark:border-gray-700">
                                <td class="px-4 py-2" colspan="4">Saldo anterior</td>
                                <td class="px-4 py-2 text-right font-mono">{{ \App\Support\Money::format($ledger['opening']) }}</td>
                            </tr>
                        @endif

                        @forelse ($ledger['movements'] as $movement)
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2 whitespace-nowrap">{{ $movement->line->entry->date->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('entries.show', $movement->line->entry) }}" class="text-blue-700 hover:underline dark:text-blue-400">No. {{ $movement->line->entry->number }}</a>
                                    — {{ $movement->line->entry->description }}
                                </td>
                                <td class="px-4 py-2 text-right font-mono">{{ $movement->debit > 0 ? \App\Support\Money::format($movement->debit) : '' }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ $movement->credit > 0 ? \App\Support\Money::format($movement->credit) : '' }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ \App\Support\Money::format($movement->balance) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">La cuenta no tiene movimientos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow dark:bg-gray-800 dark:text-gray-400">
                Selecciona una cuenta para ver sus movimientos.
            </p>
        @endif
    </div>
</body>
</html>
