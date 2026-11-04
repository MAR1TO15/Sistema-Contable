<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asientos contables - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Asientos contables</h1>
            <a
                href="{{ route('entries.create') }}"
                class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
            >
                Nuevo asiento
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">No.</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Periodo</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                        <th class="px-4 py-3">Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-3">
                                <a href="{{ route('entries.show', $entry) }}" class="font-mono text-blue-700 hover:underline dark:text-blue-400">{{ $entry->number }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $entry->description }}</td>
                            <td class="px-4 py-3">{{ $entry->fiscalPeriod->name }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ \App\Support\Money::format(\App\Support\Money::toCents($entry->lines_sum_debit)) }}</td>
                            <td class="px-4 py-3">{{ $entry->user->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Esta empresa todavía no tiene asientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
