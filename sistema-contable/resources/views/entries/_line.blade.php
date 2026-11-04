@php
    $cellInput = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
@endphp
<tr class="border-t border-gray-200 dark:border-gray-700">
    <td class="px-3 py-2">
        <select name="lines[{{ $index }}][account_id]" class="{{ $cellInput }}">
            <option value="">Selecciona una cuenta</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected(($line['account_id'] ?? null) == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
            @endforeach
        </select>
    </td>
    <td class="px-3 py-2">
        <input type="text" name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" maxlength="255" class="{{ $cellInput }}">
    </td>
    <td class="px-3 py-2">
        <input type="text" inputmode="decimal" name="lines[{{ $index }}][debit]" value="{{ $line['debit'] ?? '' }}" placeholder="0.00" data-debit
            class="{{ $cellInput }} text-right font-mono">
    </td>
    <td class="px-3 py-2">
        <input type="text" inputmode="decimal" name="lines[{{ $index }}][credit]" value="{{ $line['credit'] ?? '' }}" placeholder="0.00" data-credit
            class="{{ $cellInput }} text-right font-mono">
    </td>
    <td class="px-3 py-2 text-center">
        <button type="button" data-remove-line title="Quitar línea" class="text-lg leading-none text-gray-400 hover:text-red-700 dark:hover:text-red-400">&times;</button>
    </td>
</tr>
