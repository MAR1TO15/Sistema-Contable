@foreach ($accountsByParent->get($parentKey, collect()) as $account)
    <tr class="border-t border-gray-200 dark:border-gray-700">
        <td class="px-4 py-2 font-mono">{{ $account->code }}</td>
        <td class="px-4 py-2" style="padding-left: {{ 1 + $depth * 1.5 }}rem">
            <span class="{{ $account->is_postable ? '' : 'font-semibold text-gray-900 dark:text-white' }}">{{ $account->name }}</span>
        </td>
        <td class="px-4 py-2">{{ $account->type->label() }}</td>
        <td class="px-4 py-2">{{ $account->nature->label() }}</td>
        <td class="px-4 py-2">
            @if ($account->is_postable)
                <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-300">Imputable</span>
            @else
                <span class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">Agrupación</span>
            @endif
        </td>
        <td class="px-4 py-2 whitespace-nowrap">
            <a href="{{ route('accounts.edit', $account) }}" class="mr-3 text-blue-700 hover:underline dark:text-blue-400">Editar</a>
            <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="inline"
                onsubmit="return confirm('¿Eliminar la cuenta {{ $account->code }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-700 hover:underline dark:text-red-400">Eliminar</button>
            </form>
        </td>
    </tr>

    @include('accounts._tree', ['parentKey' => $account->id, 'depth' => $depth + 1])
@endforeach
