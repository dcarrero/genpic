<x-filament-panels::page>
    <form wire:submit="import">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Importar contactos</span>
                <span wire:loading>Procesando CSV...</span>
            </x-filament::button>
        </div>
    </form>

    @if($imported)
    <div class="mt-6 p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Resultado de la importación</h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalRows }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Filas procesadas</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg dark:bg-green-900/20">
                <p class="text-2xl font-bold text-green-600">{{ $newUsers }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nuevos usuarios</p>
            </div>
            <div class="text-center p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20">
                <p class="text-2xl font-bold text-blue-600">{{ $existingUsers }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Ya existían</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg dark:bg-red-900/20">
                <p class="text-2xl font-bold text-red-600">{{ $optedOut }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Desuscritos (no se enviarán)</p>
            </div>
        </div>

        @if(!empty($langDistribution))
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Distribución por idioma</h4>
        <div class="flex flex-wrap gap-3">
            @foreach($langDistribution as $lang => $count)
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400">
                {{ strtoupper($lang) }}: {{ number_format($count) }}
            </span>
            @endforeach
        </div>
        @endif
    </div>
    @endif
    {{-- Sección: Limpiar emails devueltos --}}
    <div class="mt-10 p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Limpiar emails devueltos (bounces)</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Pega los emails devueltos (uno por línea). Se eliminarán de la base de datos si no tienen pedidos pagados.
        </p>

        <textarea
            wire:model="bouncedEmails"
            rows="6"
            placeholder="email1@ejemplo.com&#10;email2@ejemplo.com&#10;email3@ejemplo.com"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono focus:border-primary-500 focus:ring-primary-500"
        ></textarea>

        <div class="mt-3">
            <x-filament::button wire:click="cleanBounces" wire:loading.attr="disabled" color="danger">
                <span wire:loading.remove wire:target="cleanBounces">Eliminar emails devueltos</span>
                <span wire:loading wire:target="cleanBounces">Procesando...</span>
            </x-filament::button>
        </div>

        @if($cleaned && !empty($cleanLog))
        <div class="mt-4 space-y-1 max-h-80 overflow-y-auto">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Resultado de la limpieza</h4>
            @foreach($cleanLog as $entry)
            <div class="flex items-center gap-2 text-sm py-1 px-2 rounded {{ match($entry['status']) {
                'success' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400',
                'warning' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400',
                default   => 'bg-gray-50 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
            } }}">
                <span class="font-mono">{{ $entry['email'] }}</span>
                <span>—</span>
                <span>{{ $entry['action'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-filament-panels::page>
