<x-filament-panels::page>
    {{-- Presupuesto diario --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500">Coste IA hoy</p>
                <p class="text-3xl font-bold {{ $todayCost >= $dailyBudget ? 'text-red-600' : 'text-green-600' }}">
                    ${{ number_format($todayCost, 2) }}
                </p>
                <p class="text-xs text-gray-400">Presupuesto: ${{ number_format($dailyBudget, 2) }}/día</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500">Cadena de proveedores</p>
                <p class="text-lg font-semibold">{{ implode(' → ', $chain) }}</p>
                <p class="text-xs text-gray-400">Orden de prioridad (fallback automático)</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500">Cooldown</p>
                <p class="text-sm">Timeout: {{ config('image_providers.cooldown_timeout', 300) }}s</p>
                <p class="text-sm">Error: {{ config('image_providers.cooldown_error', 120) }}s</p>
            </div>
        </x-filament::section>
    </div>

    {{-- Proveedores --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($providerStats as $key => $data)
        @php $config = $data['config']; @endphp
        <x-filament::section heading="{{ strtoupper($key) }}" icon="heroicon-o-cpu-chip">
            <div class="space-y-3">
                {{-- Estado --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Estado</span>
                    @if($config['enabled'] ?? false)
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold bg-green-100 text-green-700 rounded-full">
                            ✓ Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold bg-red-100 text-red-700 rounded-full">
                            ✗ Desactivado
                        </span>
                    @endif
                </div>

                {{-- Modelo --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Modelo</span>
                    <span class="text-sm font-mono">{{ $config['model'] ?? $config['default_model'] ?? '—' }}</span>
                </div>

                {{-- Coste por imagen --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Coste/imagen</span>
                    <span class="text-sm font-semibold">${{ number_format($config['cost_per_image'] ?? 0, 3) }}</span>
                </div>

                {{-- Timeout --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Timeout</span>
                    <span class="text-sm">{{ $config['timeout_seconds'] ?? 120 }}s</span>
                </div>

                {{-- API Key --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">API Key</span>
                    @if(!empty($config['key']))
                        <span class="text-sm text-green-600 font-semibold">✓ Configurada ({{ substr($config['key'], 0, 8) }}...)</span>
                    @else
                        <span class="text-sm text-red-600 font-semibold">✗ No configurada</span>
                    @endif
                </div>

                {{-- Stats últimos 7 días --}}
                <div class="pt-3 mt-3 border-t border-gray-200">
                    <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Últimos 7 días</p>
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div>
                            <p class="text-lg font-bold">{{ $data['total_calls'] }}</p>
                            <p class="text-[10px] text-gray-400">Llamadas</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-green-600">{{ $data['success_calls'] }}</p>
                            <p class="text-[10px] text-gray-400">OK</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-red-600">{{ $data['error_calls'] }}</p>
                            <p class="text-[10px] text-gray-400">Errores</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold">${{ number_format($data['total_cost'], 2) }}</p>
                            <p class="text-[10px] text-gray-400">Coste</p>
                        </div>
                    </div>
                    @if($data['avg_elapsed'])
                    <p class="text-xs text-gray-400 mt-1 text-center">
                        Tiempo medio: {{ number_format($data['avg_elapsed'] / 1000, 1) }}s
                    </p>
                    @endif
                </div>
            </div>
        </x-filament::section>
        @endforeach
    </div>

    {{-- Ayuda .env --}}
    <x-filament::section heading="Configuración .env" icon="heroicon-o-document-text" collapsible collapsed>
        <div class="prose prose-sm max-w-none">
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto">
# Cadena de proveedores (orden de fallback)
# Opciones: fal_ai, openrouter
IMAGE_PROVIDERS_CHAIN=fal_ai,openrouter

# Presupuesto diario máximo en USD
DAILY_COST_BUDGET_USD=50

# --- fal.ai ---
FAL_AI_ENABLED=true
FAL_AI_KEY=tu_clave_aqui

# --- OpenRouter ---
OPENROUTER_ENABLED=false
OPENROUTER_API_KEY=tu_clave_aqui
OPENROUTER_DEFAULT_MODEL=seedream-4.5

# --- Para añadir un nuevo proveedor ---
# 1. Crear clase en app/Services/ImageGeneration/Providers/
# 2. Implementar ImageProviderInterface
# 3. Registrar en ImageGenerationService::$providerMap
# 4. Añadir config en config/image_providers.php
# 5. Añadir a la cadena: IMAGE_PROVIDERS_CHAIN=fal_ai,nuevo,openrouter</pre>
        </div>
    </x-filament::section>
</x-filament-panels::page>
