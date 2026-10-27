<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contadores - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contadores</h1>
            <a
                href="{{ route('accountants.create') }}"
                class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
            >
                Nuevo contador
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Clientes asignados</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accountants as $accountant)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-3">{{ $accountant->name }}</td>
                            <td class="px-4 py-3">{{ $accountant->email }}</td>
                            <td class="px-4 py-3">{{ $accountant->clients_count }}</td>
                            <td class="px-4 py-3">
                                @if ($accountant->is_active)
                                    <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-300">Activo</span>
                                @else
                                    <span class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('accountants.edit', $accountant) }}" class="mr-3 text-blue-700 hover:underline dark:text-blue-400">Editar</a>
                                <form method="POST" action="{{ route('accountants.toggle', $accountant) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-red-700 hover:underline dark:text-red-400">
                                        {{ $accountant->is_active ? 'Desactivar' : 'Reactivar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Tu firma todavía no tiene contadores registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
