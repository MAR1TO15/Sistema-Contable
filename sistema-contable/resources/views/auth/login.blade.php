<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-8 shadow dark:bg-gray-800">
            <h1 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">
                Iniciar sesión
            </h1>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400" role="alert">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">
                        Correo electrónico
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">
                        Contraseña
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <div class="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-gray-300 bg-gray-50 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                    >
                    <label for="remember" class="ms-2 text-sm text-gray-600 dark:text-gray-300">
                        Recordarme
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-blue-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                >
                    Ingresar
                </button>
            </form>
        </div>
    </div>
</body>
</html>
