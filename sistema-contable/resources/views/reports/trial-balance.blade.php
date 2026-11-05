<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balance de comprobación - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Balance de comprobación</h1>

        @include('reports._period_filter', ['action' => route('reports.trial-balance')])

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3 text-right">Débitos</th>
                        <th class="px-4 py-3 text-right">Créditos</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr class="border-t border-gray-200 dark:border-gray-700 {{ $balance->account->is_postable ? '' : 'font-semibold text-gray-900 dark:text-white' }}">
                            <td class="px-4 py-2 font-mono">{{ $balance->account->code }}</td>
                            <td class="px-4 py-2">
                                {{ $balance->account->name }}
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ strtolower($balance->account->nature->label()) }})</span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono">{{ \App\Support\Money::format($balance->debit) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ \App\Support\Money::format($balance->credit) }}</td>
                            <td class="px-4 py-2 text-right font-mono {{ $balance->balance < 0 ? 'text-red-700 dark:text-red-400' : '' }}">{{ \App\Support\Money::format($balance->balance) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Esta empresa todavía no tiene cuentas.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-gray-300 font-semibold text-gray-900 dark:border-gray-600 dark:text-white">
                    <tr>
                        <td class="px-4 py-3" colspan="2">Totales (cuentas imputables)</td>
                        <td class="px-4 py-3 text-right font-mono">{{ \App\Support\Money::format($totalDebit) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ \App\Support\Money::format($totalCredit) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($totalDebit === $totalCredit)
            <p class="mt-4 text-sm text-green-700 dark:text-green-400">El balance cuadra: los débitos son iguales a los créditos.</p>
        @else
            <p class="mt-4 text-sm text-red-700 dark:text-red-400">
                El balance no cuadra: diferencia de {{ \App\Support\Money::format(abs($totalDebit - $totalCredit)) }}.
            </p>
        @endif
    </div>
</body>
</html>
