<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva firma - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-md px-4 py-8">
        <a href="{{ route('firms.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Firmas</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Nueva firma</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('firms.store') }}" class="space-y-5">
            @csrf

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Datos de la firma</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nombre / razón social</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="tax_id" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">RUC/NIT (opcional)</label>
                        <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Administrador de la firma</h2>

                <div class="space-y-4">
                    <div>
                        <label for="admin_name" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nombre</label>
                        <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="admin_email" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Correo electrónico</label>
                        <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="admin_password" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Contraseña</label>
                        <input id="admin_password" name="admin_password" type="password" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="admin_password_confirmation" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Confirmar contraseña</label>
                        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                Guardar
            </button>
        </form>
    </div>
</body>
</html>
