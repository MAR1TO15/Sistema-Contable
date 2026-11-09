<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo periodo - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-md px-4 py-8">
        <a href="{{ route('periods.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Periodos fiscales</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Nuevo periodo fiscal</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('periods.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="100" placeholder="Ejercicio 2026"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="start_date" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Fecha inicial</label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="end_date" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Fecha final</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">El periodo se crea abierto. No puede traslaparse con otro periodo de esta empresa.</p>

            <button type="submit" class="w-full rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                Guardar
            </button>
        </form>
    </div>
</body>
</html>
