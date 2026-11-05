<form method="GET" action="{{ $action }}" class="mb-6 flex flex-wrap items-end gap-3">
    @foreach ($keep ?? [] as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    <div>
        <label for="period" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Periodo</label>
        <select id="period" name="period" onchange="this.form.submit()"
            class="block rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="">Todos los periodos</option>
            @foreach ($periods as $option)
                <option value="{{ $option->id }}" @selected($period?->id === $option->id)>{{ $option->name }}</option>
            @endforeach
        </select>
    </div>

    <noscript>
        <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Filtrar</button>
    </noscript>
</form>
