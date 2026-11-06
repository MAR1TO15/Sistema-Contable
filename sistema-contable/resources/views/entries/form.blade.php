<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $entry ? 'Editar asiento '.$entry->number : 'Nuevo asiento' }} - Sistema Contable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    @php
        $lines = old('lines', $defaultLines);
        $inputClass = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
    @endphp

    <div class="mx-auto max-w-5xl px-4 py-8">
        <a href="{{ route('entries.index') }}" class="mb-4 inline-block text-sm text-blue-700 hover:underline dark:text-blue-400">&larr; Asientos contables</a>

        <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">{{ $entry ? 'Editar asiento No. '.$entry->number : 'Nuevo asiento' }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($openPeriods->isEmpty())
            <div class="mb-4 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                Esta empresa no tiene periodos fiscales abiertos.
                <a href="{{ route('periods.create') }}" class="font-medium underline">Crea un periodo</a> antes de registrar asientos.
            </div>
        @endif

        <form method="POST" action="{{ $entry ? route('entries.update', $entry) : route('entries.store') }}" class="space-y-6">
            @csrf
            @if ($entry)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="date" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Fecha</label>
                    <input id="date" name="date" type="date" value="{{ old('date', $entry?->date->toDateString()) }}" required class="{{ $inputClass }}">
                    @if ($openPeriods->isNotEmpty())
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Periodos abiertos:
                            {{ $openPeriods->map(fn ($p) => $p->name.' ('.$p->start_date->format('d/m/Y').' - '.$p->end_date->format('d/m/Y').')')->implode(', ') }}
                        </p>
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">Descripción</label>
                    <input id="description" name="description" type="text" value="{{ old('description', $entry?->description) }}" required maxlength="255" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-3 py-3">Cuenta</th>
                            <th class="px-3 py-3">Detalle</th>
                            <th class="w-36 px-3 py-3 text-right">Débito</th>
                            <th class="w-36 px-3 py-3 text-right">Crédito</th>
                            <th class="w-10 px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="lines">
                        @foreach ($lines as $index => $line)
                            @include('entries._line', ['index' => $index, 'line' => $line])
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-300 font-semibold text-gray-900 dark:border-gray-600 dark:text-white">
                        <tr>
                            <td class="px-3 py-3" colspan="2">
                                <button type="button" id="add-line" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">+ Agregar línea</button>
                            </td>
                            <td class="px-3 py-3 text-right font-mono" id="total-debit">0.00</td>
                            <td class="px-3 py-3 text-right font-mono" id="total-credit">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="px-3 pb-3 text-right text-sm" colspan="4" id="balance-status"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                {{ $entry ? 'Guardar cambios' : 'Registrar asiento' }}
            </button>
        </form>
    </div>

    <template id="line-template">
        @include('entries._line', ['index' => '__INDEX__', 'line' => []])
    </template>

    <script>
        (function () {
            const body = document.getElementById('lines');
            const template = document.getElementById('line-template').innerHTML;
            let nextIndex = {{ count($lines) }};

            // Amounts are summed in cents so the live total matches the server.
            const toCents = (value) => {
                const match = String(value).trim().match(/^(\d+)(?:\.(\d{1,2}))?$/);
                return match ? parseInt(match[1], 10) * 100 + parseInt((match[2] || '').padEnd(2, '0') || '0', 10) : 0;
            };
            const format = (cents) => (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function updateTotals() {
                let debit = 0;
                let credit = 0;
                body.querySelectorAll('[data-debit]').forEach((input) => debit += toCents(input.value));
                body.querySelectorAll('[data-credit]').forEach((input) => credit += toCents(input.value));

                document.getElementById('total-debit').textContent = format(debit);
                document.getElementById('total-credit').textContent = format(credit);

                const status = document.getElementById('balance-status');
                if (debit === 0 && credit === 0) {
                    status.textContent = '';
                } else if (debit === credit) {
                    status.textContent = 'El asiento cuadra.';
                    status.className = 'px-3 pb-3 text-right text-sm text-green-700 dark:text-green-400';
                } else {
                    status.textContent = 'Diferencia: ' + format(Math.abs(debit - credit));
                    status.className = 'px-3 pb-3 text-right text-sm text-red-700 dark:text-red-400';
                }
            }

            document.getElementById('add-line').addEventListener('click', () => {
                body.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex++));
            });

            body.addEventListener('click', (event) => {
                if (event.target.matches('[data-remove-line]') && body.children.length > 2) {
                    event.target.closest('tr').remove();
                    updateTotals();
                }
            });

            body.addEventListener('input', updateTotals);
            updateTotals();
        })();
    </script>
</body>
</html>
