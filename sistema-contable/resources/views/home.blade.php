<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">Sistema Contable</h1>
            <p class="mb-6 text-gray-600 dark:text-gray-400">
                Bienvenido, {{ auth()->user()->name ?? 'invitado' }}.
            </p>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-red-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                    >
                        Cerrar sesión
                    </button>
                </form>
            @endauth
        </div>
    </div>
</body>
</html>
