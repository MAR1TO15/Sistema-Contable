<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar cuenta - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-md px-4 py-8">
        <a href="{{ route('accounts.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Catálogo de cuentas</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">Editar cuenta</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <dl class="mb-5 grid grid-cols-2 gap-2 rounded-lg bg-white p-4 text-sm shadow dark:bg-gray-800">
            <dt class="text-gray-500 dark:text-gray-400">Tipo</dt>
            <dd class="text-gray-900 dark:text-white">{{ $account->type->label() }}</dd>
            <dt class="text-gray-500 dark:text-gray-400">Cuenta padre</dt>
            <dd class="text-gray-900 dark:text-white">{{ $account->parent ? $account->parent->code.' — '.$account->parent->name : 'Ninguna (raíz)' }}</dd>
        </dl>

        <form method="POST" action="{{ route('accounts.update', $account) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="code" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Código</label>
                <input id="code" name="code" type="text" value="{{ old('code', $account->code) }}" required maxlength="20"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name', $account->name) }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label for="nature" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Naturaleza</label>
                <select id="nature" name="nature"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach (\App\Enums\AccountNature::cases() as $nature)
                        <option value="{{ $nature->value }}" @selected(old('nature', $account->nature->value) === $nature->value)>{{ $nature->label() }}</option>
                    @endforeach
                </select>
            </div>

            @if ($hasChildren)
                <p class="text-sm text-gray-500 dark:text-gray-400">Esta cuenta tiene subcuentas, por lo que es de agrupación y no recibe movimientos.</p>
            @else
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="is_postable" value="0">
                    <input type="checkbox" name="is_postable" value="1" @checked(old('is_postable', $account->is_postable ? '1' : '0') === '1')
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Cuenta imputable (recibe movimientos)
                </label>
            @endif

            <button type="submit" class="w-full rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                Guardar cambios
            </button>
        </form>
    </div>
</body>
</html>
