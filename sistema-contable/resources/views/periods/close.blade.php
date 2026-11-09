<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cerrar periodo - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    @php
        $defaultEquity = old('equity_account_id', $equityAccounts->firstWhere('code', '3102')?->id ?? $equityAccounts->first()?->id);
    @endphp

    <div class="mx-auto max-w-xl px-4 py-8">
        <a href="{{ route('periods.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Periodos fiscales</a>

        <h1 class="mb-1 text-2xl font-bold text-gray-900 dark:text-white">Cerrar {{ $period->name }}</h1>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            Del {{ $period->start_date->format('d/m/Y') }} al {{ $period->end_date->format('d/m/Y') }} · {{ $openEntries }} asientos registrados
        </p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <dl class="mb-6 space-y-2 rounded-lg bg-white p-5 text-sm shadow dark:bg-gray-800">
            <div class="flex justify-between">
                <dt class="text-gray-600 dark:text-gray-400">Total ingresos</dt>
                <dd class="font-mono text-gray-900 dark:text-white">{{ \App\Support\Money::format($statement->totalIncome) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-600 dark:text-gray-400">Total gastos</dt>
                <dd class="font-mono text-gray-900 dark:text-white">{{ \App\Support\Money::format($statement->totalExpenses) }}</dd>
            </div>
            <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold dark:border-gray-700 {{ $statement->isLoss() ? 'text-red-700 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}">
                <dt>{{ $statement->isLoss() ? 'Pérdida del periodo' : 'Utilidad del periodo' }}</dt>
                <dd class="font-mono">{{ \App\Support\Money::format(abs($statement->result)) }}</dd>
            </div>
        </dl>

        <div class="mb-6 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
            Al cerrar el periodo se genera un asiento de cierre que deja en cero las cuentas de ingresos y gastos
            y traslada el resultado a la cuenta de patrimonio que elijas. Después ya no se podrán registrar, modificar
            ni eliminar asientos de este periodo. <strong>Esta acción no se puede deshacer.</strong>
        </div>

        <form method="POST" action="{{ route('periods.close', $period) }}" class="space-y-5"
            onsubmit="return confirm('¿Cerrar {{ $period->name }}? Esta acción no se puede deshacer.')">
            @csrf

            <div>
                <label for="equity_account_id" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Cuenta de patrimonio que recibe el resultado</label>
                @if ($equityAccounts->isEmpty())
                    <p class="text-sm text-red-700 dark:text-red-400">
                        Esta empresa no tiene cuentas imputables de patrimonio.
                        <a href="{{ route('accounts.create') }}" class="font-medium underline">Crea una</a> antes de cerrar el periodo.
                    </p>
                @else
                    <select id="equity_account_id" name="equity_account_id" required
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach ($equityAccounts as $account)
                            <option value="{{ $account->id }}" @selected($defaultEquity == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <button type="submit" @disabled($equityAccounts->isEmpty())
                class="w-full rounded-lg bg-red-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50">
                Cerrar periodo
            </button>
        </form>
    </div>
</body>
</html>
