<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Periodos fiscales - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Periodos fiscales</h1>
            <a
                href="{{ route('periods.create') }}"
                class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
            >
                Nuevo periodo
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Inicio</th>
                        <th class="px-4 py-3">Fin</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periods as $period)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-3">{{ $period->name }}</td>
                            <td class="px-4 py-3">{{ $period->start_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $period->end_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($period->status === \App\Enums\PeriodStatus::Abierto)
                                    <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-300">{{ $period->status->label() }}</span>
                                @else
                                    <span class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $period->status->label() }}</span>
                                    @if ($period->closedBy)
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                            por {{ $period->closedBy->name }} el {{ $period->closed_at->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @can('close', $period)
                                    <a href="{{ route('periods.confirm-close', $period) }}" class="text-red-700 hover:underline dark:text-red-400">Cerrar periodo</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Esta empresa todavía no tiene periodos fiscales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
