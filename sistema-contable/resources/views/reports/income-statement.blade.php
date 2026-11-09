<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de resultados - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Estado de resultados</h1>

        @if (! $period)
            <div class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow dark:bg-gray-800 dark:text-gray-400">
                Esta empresa todavía no tiene periodos fiscales.
                <a href="{{ route('periods.create') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">Crea un periodo</a>
                para registrar asientos y ver sus resultados.
            </div>
        @else
            <form method="GET" action="{{ route('reports.income-statement') }}" class="mb-6">
                <label for="period" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Periodo</label>
                <select id="period" name="period" onchange="this.form.submit()"
                    class="block rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach ($periods as $option)
                        <option value="{{ $option->id }}" @selected($period->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <noscript>
                    <button type="submit" class="mt-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Consultar</button>
                </noscript>
            </form>

            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Del {{ $period->start_date->format('d/m/Y') }} al {{ $period->end_date->format('d/m/Y') }}
            </p>

            <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    @foreach ([['Ingresos', $statement->income, 'Total ingresos', $statement->totalIncome], ['Gastos', $statement->expenses, 'Total gastos', $statement->totalExpenses]] as [$title, $lines, $totalLabel, $total])
                        <tbody>
                            <tr class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <th class="px-4 py-3" colspan="2">{{ $title }}</th>
                            </tr>
                            @forelse ($lines as $line)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-2 pl-8"><span class="font-mono">{{ $line->account->code }}</span> {{ $line->account->name }}</td>
                                    <td class="px-4 py-2 text-right font-mono {{ $line->amount < 0 ? 'text-red-700 dark:text-red-400' : '' }}">{{ \App\Support\Money::format($line->amount) }}</td>
                                </tr>
                            @empty
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-2 pl-8 text-gray-500" colspan="2">Sin movimientos en el periodo.</td>
                                </tr>
                            @endforelse
                            <tr class="border-t border-gray-300 font-semibold text-gray-900 dark:border-gray-600 dark:text-white">
                                <td class="px-4 py-2">{{ $totalLabel }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ \App\Support\Money::format($total) }}</td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>

            @if ($statement->isLoss())
                <div class="mt-6 flex items-center justify-between rounded-lg bg-red-50 p-5 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                    <span class="text-lg font-semibold">Pérdida del periodo</span>
                    <span class="font-mono text-xl font-bold">{{ \App\Support\Money::format(abs($statement->result)) }}</span>
                </div>
            @else
                <div class="mt-6 flex items-center justify-between rounded-lg bg-green-50 p-5 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                    <span class="text-lg font-semibold">Utilidad del periodo</span>
                    <span class="font-mono text-xl font-bold">{{ \App\Support\Money::format($statement->result) }}</span>
                </div>
            @endif
        @endif
    </div>
</body>
</html>
