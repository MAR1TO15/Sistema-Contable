<tbody>
    <tr class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
        <th class="px-4 py-3" colspan="2">{{ $title }}</th>
    </tr>
    @forelse ($rows as $row)
        <tr class="border-t border-gray-200 dark:border-gray-700 {{ $row->isGroup ? 'font-semibold text-gray-900 dark:text-white' : '' }}">
            <td class="px-4 py-2" style="padding-left: {{ 1 + $row->depth * 1.5 }}rem">
                <span class="font-mono">{{ $row->account->code }}</span> {{ $row->account->name }}
            </td>
            <td class="px-4 py-2 text-right font-mono {{ $row->amount < 0 ? 'text-red-700 dark:text-red-400' : '' }}">{{ \App\Support\Money::format($row->amount) }}</td>
        </tr>
    @empty
        <tr class="border-t border-gray-200 dark:border-gray-700">
            <td class="px-4 py-2 pl-8 text-gray-500" colspan="2">Sin saldos a la fecha de corte.</td>
        </tr>
    @endforelse
    @isset($periodResult)
        <tr class="border-t border-gray-200 italic dark:border-gray-700">
            <td class="px-4 py-2 pl-8">Resultado del ejercicio <span class="text-xs not-italic text-gray-500 dark:text-gray-400">(utilidad o pérdida aún no cerrada)</span></td>
            <td class="px-4 py-2 text-right font-mono {{ $periodResult < 0 ? 'text-red-700 dark:text-red-400' : '' }}">{{ \App\Support\Money::format($periodResult) }}</td>
        </tr>
    @endisset
    <tr class="border-t border-gray-300 font-semibold text-gray-900 dark:border-gray-600 dark:text-white">
        <td class="px-4 py-2">{{ $totalLabel }}</td>
        <td class="px-4 py-2 text-right font-mono">{{ \App\Support\Money::format($total) }}</td>
    </tr>
</tbody>
