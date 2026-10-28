<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    @php
        $roleLabels = [
            'super_admin' => 'Super administrador',
            'admin_firma' => 'Administrador de firma',
            'contador' => 'Contador',
        ];
        $roleLabel = $roleLabels[auth()->user()->role->value] ?? null;
    @endphp
    @inject('activeClientService', 'App\Services\ActiveClient')
    @php($activeClient = $activeClientService->get())

    <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4">
            <span class="text-lg font-bold text-gray-900 dark:text-white">Sistema Contable</span>

            @if ($activeClient)
                <div class="ml-auto flex items-center gap-2 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Empresa:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $activeClient->name }}</span>
                    <a href="{{ route('workspace.index') }}" class="text-blue-700 hover:underline dark:text-blue-400">Cambiar</a>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-red-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-red-400"
                >
                    Cerrar sesión
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Bienvenido, {{ auth()->user()->name ?? 'invitado' }}
            </h1>
            @if ($roleLabel)
                <span class="mt-2 inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    {{ $roleLabel }}
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @can('viewAny', \App\Models\Firm::class)
                <a
                    href="{{ route('firms.index') }}"
                    class="group rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
                >
                    <h2 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-400">
                        Administrar firmas
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Crea firmas contables y gestiona su estado y administradores.
                    </p>
                </a>
            @endcan

            @can('selectAny', \App\Models\Client::class)
                <a
                    href="{{ route('workspace.index') }}"
                    class="group rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
                >
                    <h2 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-400">
                        Mis clientes
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if ($activeClient)
                            Trabajando en {{ $activeClient->name }}. Cambia de empresa cuando lo necesites.
                        @else
                            Selecciona la empresa con la que vas a trabajar.
                        @endif
                    </p>
                </a>
            @endcan

            @if ($activeClient)
                <a
                    href="{{ route('accounts.index') }}"
                    class="group rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
                >
                    <h2 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-400">
                        Catálogo de cuentas
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Organiza las cuentas contables de {{ $activeClient->name }}.
                    </p>
                </a>
            @endif

            @can('viewAny', \App\Models\Client::class)
                <a
                    href="{{ route('clients.index') }}"
                    class="group rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
                >
                    <h2 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-400">
                        Gestionar clientes
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Administra los clientes de tu firma y asigna contadores.
                    </p>
                </a>
            @endcan

            @can('viewAny', \App\Models\User::class)
                <a
                    href="{{ route('accountants.index') }}"
                    class="group rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
                >
                    <h2 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-400">
                        Gestionar contadores
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Registra a los contadores de tu firma y controla su acceso.
                    </p>
                </a>
            @endcan
        </div>
    </main>
</body>
</html>
