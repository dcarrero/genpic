<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::button wire:click="runAllTests" icon="heroicon-o-play" color="primary">
            Ejecutar todos los tests
        </x-filament::button>

        @if($tested)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($results as $key => $result)
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-4 {{ $result['status'] === 'error' ? 'border-red-300 dark:border-red-700' : ($result['status'] === 'warning' ? 'border-yellow-300 dark:border-yellow-700' : ($result['status'] === 'skip' ? 'border-gray-200 dark:border-gray-700' : 'border-green-300 dark:border-green-700')) }}">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-sm">
                        @if($result['status'] === 'ok')
                            <span class="text-green-600">✓</span>
                        @elseif($result['status'] === 'error')
                            <span class="text-red-600">✗</span>
                        @elseif($result['status'] === 'warning')
                            <span class="text-yellow-600">⚠</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                        {{ $result['name'] }}
                    </h3>
                    @if($result['ms'] > 0)
                        <span class="text-xs text-gray-400">{{ $result['ms'] }}ms</span>
                    @endif
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 break-all">{{ Str::limit($result['message'], 200) }}</p>

                @if($result['status'] !== 'ok' && $result['status'] !== 'skip')
                <div class="mt-2">
                    <x-filament::button wire:click="testSingleService('{{ $key }}')" size="xs" color="gray" icon="heroicon-o-arrow-path">
                        Reintentar
                    </x-filament::button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="font-semibold text-sm mb-2">Info del sistema</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs text-gray-600 dark:text-gray-400">
                <div><span class="font-medium">PHP:</span> {{ PHP_VERSION }}</div>
                <div><span class="font-medium">Laravel:</span> {{ app()->version() }}</div>
                <div><span class="font-medium">GenPic:</span> v{{ config('app.version', '?') }}</div>
                <div><span class="font-medium">Entorno:</span> {{ config('app.env') }}</div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
