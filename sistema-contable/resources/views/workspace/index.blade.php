<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis clientes - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mis clientes</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Selecciona la empresa con la que vas a trabajar. Todas las operaciones contables se registrarán sobre ella.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @forelse ($clients as $client)
                <div class="flex items-center justify-between rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-white">{{ $client->name }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">RUC/NIT: {{ $client->tax_id ?? '—' }}</p>
                    </div>

                    @if ($client->id === $activeClientId)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                            Empresa actual
                        </span>
                    @else
                        <form method="POST" action="{{ route('workspace.select', $client) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                                Trabajar aquí
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow sm:col-span-2 dark:bg-gray-800 dark:text-gray-400">
                    Aún no tienes clientes asignados. Cuando tu firma te asigne clientes, aparecerán aquí.
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>
