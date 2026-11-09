<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balance general - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Balance general</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('reports.balance-sheet') }}" class="mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="date" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Fecha de corte</label>
                <input id="date" name="date" type="date" value="{{ $sheet->cutoff }}"
                    class="block rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Consultar</button>
        </form>

        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Al {{ \Illuminate\Support\Carbon::parse($sheet->cutoff)->format('d/m/Y') }}
        </p>

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                @include('reports._balance_section', ['title' => 'Activo', 'rows' => $sheet->assets, 'totalLabel' => 'Total activo', 'total' => $sheet->totalAssets])
                @include('reports._balance_section', ['title' => 'Pasivo', 'rows' => $sheet->liabilities, 'totalLabel' => 'Total pasivo', 'total' => $sheet->totalLiabilities])
                @include('reports._balance_section', [
                    'title' => 'Patrimonio',
                    'rows' => $sheet->equity,
                    'periodResult' => $sheet->periodResult,
                    'totalLabel' => 'Total patrimonio',
                    'total' => $sheet->totalEquity,
                ])
                <tbody>
                    <tr class="border-t-2 border-gray-400 font-semibold text-gray-900 dark:border-gray-500 dark:text-white">
                        <td class="px-4 py-3">Total pasivo y patrimonio</td>
                        <td class="px-4 py-3 text-right font-mono">{{ \App\Support\Money::format($sheet->totalLiabilities + $sheet->totalEquity) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($sheet->isBalanced())
            <p class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-300">
                La ecuación contable se cumple: Activo = Pasivo + Patrimonio.
            </p>
        @else
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-300">
                <p class="font-semibold">La ecuación contable no cuadra.</p>
                <p>Diferencia entre el activo y el pasivo más patrimonio: <span class="font-mono">{{ \App\Support\Money::format($sheet->difference) }}</span>.
                    Revisa los asientos registrados hasta la fecha de corte.</p>
            </div>
        @endif
    </div>
</body>
</html>
