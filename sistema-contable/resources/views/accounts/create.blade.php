<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva cuenta - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-md px-4 py-8">
        <a href="{{ route('accounts.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Catálogo de cuentas</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Nueva cuenta</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('accounts.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="parent_id" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Cuenta padre</label>
                <select id="parent_id" name="parent_id"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Ninguna (cuenta raíz)</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('parent_id') == $group->id)>{{ $group->code }} — {{ $group->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Solo aparecen cuentas de agrupación. Una subcuenta hereda el tipo de su padre.</p>
            </div>

            <div>
                <label for="code" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Código</label>
                <input id="code" name="code" type="text" value="{{ old('code') }}" required maxlength="20"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="type" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Tipo</label>
                <select id="type" name="type"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">—</option>
                    @foreach (\App\Enums\AccountType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Obligatorio solo para cuentas raíz.</p>
            </div>

            <div>
                <label for="nature" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Naturaleza</label>
                <select id="nature" name="nature"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Automática (según el tipo o la cuenta padre)</option>
                    @foreach (\App\Enums\AccountNature::cases() as $nature)
                        <option value="{{ $nature->value }}" @selected(old('nature') === $nature->value)>{{ $nature->label() }}</option>
                    @endforeach
                </select>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="is_postable" value="0">
                <input type="checkbox" name="is_postable" value="1" @checked(old('is_postable', '1') === '1')
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Cuenta imputable (recibe movimientos)
            </label>

            <button type="submit" class="w-full rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                Guardar
            </button>
        </form>
    </div>
</body>
</html>
