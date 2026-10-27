<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar contador - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-md px-4 py-8">
        <a href="{{ route('accountants.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Contadores</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Editar contador</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('accountants.update', $accountant) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name', $accountant->name) }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email', $accountant->email) }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nueva contraseña (opcional)</label>
                <input id="password" name="password" type="password"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Déjala en blanco para conservar la contraseña actual.</p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Confirmar nueva contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                Guardar cambios
            </button>
        </form>
    </div>
</body>
</html>
