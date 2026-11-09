<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de cuentas - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('home') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Inicio</a>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Catálogo de cuentas</h1>
            <a
                href="{{ route('accounts.create') }}"
                class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
            >
                Nueva cuenta
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($hasAccounts)
            <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Naturaleza</th>
                            <th class="px-4 py-3">Clase</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('accounts._tree', ['parentKey' => 0, 'depth' => 0])
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Esta empresa todavía no tiene cuentas. Puedes crearlas una por una o empezar con un catálogo base.
                </p>
                <form method="POST" action="{{ route('accounts.base-catalog') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-blue-700 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-gray-700">
                        Cargar catálogo base
                    </button>
                </form>
            </div>
        @endif
    </div>
</body>
</html>
