<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Libro diario - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Libro diario</h1>

        @include('reports._period_filter', ['action' => route('reports.journal')])

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3 text-right">Débito</th>
                        <th class="px-4 py-3 text-right">Crédito</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="border-t-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
                            <td class="px-4 py-2 whitespace-nowrap">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2" colspan="3">
                                <a href="{{ route('entries.show', $entry) }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">No. {{ $entry->number }}</a>
                                — {{ $entry->description }}
                            </td>
                        </tr>
                        @foreach ($entry->lines as $line)
                            @php($credit = \App\Support\Money::toCents($line->credit))
                            <tr>
                                <td></td>
                                <td class="px-4 py-1 {{ $credit > 0 ? 'pl-10' : '' }}">
                                    <span class="font-mono">{{ $line->account->code }}</span> {{ $line->account->name }}
                                </td>
                                <td class="px-4 py-1 text-right font-mono">
                                    @if (\App\Support\Money::toCents($line->debit) > 0)
                                        {{ \App\Support\Money::format(\App\Support\Money::toCents($line->debit)) }}
                                    @endif
                                </td>
                                <td class="px-4 py-1 text-right font-mono">
                                    @if ($credit > 0)
                                        {{ \App\Support\Money::format($credit) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay asientos registrados.</td>
                        </tr>
                    @endforelse
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
