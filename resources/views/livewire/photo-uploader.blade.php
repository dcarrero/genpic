{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     paso 1-2 del funnel: subida + verificacion --}}
<div x-data="faceDetector()" x-init="loadModel()">
    {{-- role=alert: el error aparece por Livewire sin recargar; sin esto el
         lector de pantalla no se entera de nada. --}}
    @if($error)
    <div role="alert" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 text-center">
        <span class="sr-only">{{ __('ui.a11y_error') }}: </span>{{ $error }}
    </div>
    @endif

    {{-- STEP: Upload + Email (un solo formulario) --}}
    @if($step === 'upload')
    <div class="space-y-8 text-center">
        {{-- Estilo seleccionado --}}
        <div class="flex items-center justify-center gap-3">
            <span class="text-sm text-pmf-muted">{{ __('ui.style_label') }}</span>
            <span class="bg-burgundy text-white text-xs font-semibold px-4 py-1.5 rounded-full tracking-wide">
                {{ $styleKey }}
            </span>
        </div>

        <h1 class="font-serif text-4xl font-bold text-pmf-text">{{ __('ui.upload_title') }}</h1>
        <p class="font-serif text-pmf-secondary max-w-lg mx-auto leading-relaxed">{{ __('ui.upload_desc') }}</p>

        {{-- Drop zone --}}
        <div
            x-data="{ uploading: false, progress: 0 }"
            x-on:livewire-upload-start="uploading = true"
            x-on:livewire-upload-finish="uploading = false; progress = 0"
            x-on:livewire-upload-error="uploading = false; progress = 0"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave="dragging = false"
            x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
            class="w-full max-w-lg mx-auto"
        >
            {{-- El borde refleja el estado: error > arrastrando > reposo. Antes el
                 @error se pintaba fuera de la zona y el borde seguía neutro. --}}
            <label
                for="photo-input"
                @class([
                    'block h-56 border-2 border-dashed rounded-2xl shadow-sm transition cursor-pointer flex flex-col items-center justify-center gap-3',
                    'focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-burgundy',
                    'border-red-400 bg-red-50' => $errors->has('photo'),
                    'hover:border-burgundy hover:shadow-md' => ! $errors->has('photo'),
                ])
                @if(! $errors->has('photo'))
                    :class="dragging ? 'border-burgundy bg-burgundy-soft/30 shadow-md' : 'border-pmf-border bg-white'"
                @endif
            >
                {{-- Estado por defecto: HTML real, NO <template>.
                     Un <template> es inerte sin JS, así que su contenido solo
                     existe si Alpine arranca. Cuando Alpine se cayó, este cuadro
                     se quedó literalmente en blanco — funcionaba (el <label> es
                     nativo) pero no decía qué era. La instrucción no puede
                     depender de JS: x-show la oculta cuando hay preview. --}}
                <div x-show="!localPreviewUrl" class="flex flex-col items-center gap-2 px-6 text-center">
                    <svg class="w-9 h-9 text-burgundy mb-1" aria-hidden="true" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <path d="M12 3v13"/>
                        <path d="M7 8l5-5 5 5"/>
                    </svg>
                    <p class="font-serif text-lg font-semibold text-burgundy">{{ __('ui.upload_choose') }}</p>
                    <p class="text-sm text-pmf-secondary">{{ __('ui.upload_drag') }}</p>
                    <p class="text-xs text-pmf-muted tracking-wide">{{ __('ui.upload_format') }}</p>
                </div>

                {{-- Preview local desde el navegador (sin round-trip al servidor).
                     x-cloak: sin Alpine el :src nunca se resuelve, así que este
                     bloque debe quedarse oculto en vez de mostrar una imagen rota. --}}
                <div x-show="localPreviewUrl" x-cloak class="flex flex-col items-center gap-1">
                    <img :src="localPreviewUrl" class="h-40 rounded-lg object-cover" alt="{{ __('ui.upload_preview_alt') }}">
                    <p class="text-xs text-pmf-muted" x-text="fileName"></p>
                </div>
                {{-- sr-only, no hidden: display:none saca el input del orden de
                     tabulación y deja el paso inalcanzable por teclado. --}}
                <input type="file" id="photo-input" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" x-ref="fileInput"
                    @if($errors->has('photo')) aria-invalid="true" aria-describedby="photo-error" @endif
                    x-on:change="handleFileSelect($event)">
            </label>

            {{-- Progreso real de subida. El localPreviewUrl es instantáneo y hacía
                 creer que ya había terminado mientras el fichero seguía subiendo. --}}
            <div x-show="uploading" x-cloak class="mt-3" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progress" :aria-valuetext="`${progress}%`">
                <div class="h-1.5 w-full bg-pmf-border rounded-full overflow-hidden">
                    <div class="h-full bg-burgundy rounded-full transition-[width] duration-200 ease-out" :style="`width: ${progress}%`"></div>
                </div>
                <p class="text-xs text-pmf-muted mt-1.5" x-text="`{{ __('ui.upload_uploading') }} ${progress}%`"></p>
            </div>
        </div>
        @error('photo')
            <p id="photo-error" role="alert" class="text-sm text-red-600 max-w-lg mx-auto">{{ $message }}</p>
        @enderror

        {{-- Qué foto sirve. Estos consejos vivían DESPUÉS del cuadro, donde ya no
             ayudan: para entonces el usuario ya ha elegido. Aquí orientan la
             decisión. Los ✓/✗ son decorativos — la etiqueta ya lo dice. --}}
        <div class="max-w-lg mx-auto text-left">
            <p class="text-xs font-semibold tracking-wide uppercase text-pmf-muted mb-2">
                {{ __('ui.upload_tips_title') }}
            </p>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                @foreach(['tip_faces', 'tip_light', 'tip_groups'] as $tip)
                    <li class="flex items-start gap-2 text-pmf-secondary">
                        <span aria-hidden="true" class="text-green-600 font-bold leading-6">✓</span>
                        <span>{{ __("ui.{$tip}") }}</span>
                    </li>
                @endforeach
                <li class="flex items-start gap-2 text-pmf-secondary">
                    <span aria-hidden="true" class="text-red-500 font-bold leading-6">✗</span>
                    <span>{{ __('ui.tip_sunglasses') }}</span>
                </li>
            </ul>
        </div>

        {{-- Face detection status.
             aria-live=polite: Alpine cambia estos bloques sin recargar y el
             lector de pantalla no se enteraba de si había cara o no — que es
             justo lo que decide si puedes continuar. --}}
        @if($photoReady && $faceDetectionEnabled)
        <div class="max-w-lg mx-auto" aria-live="polite" aria-atomic="true">
            <div x-show="detecting" class="flex items-center justify-center gap-2 text-sm text-pmf-muted">
                <svg class="w-4 h-4 animate-spin" aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 60" stroke-linecap="round"/></svg>
                {{ __('ui.face_detecting') }}
            </div>
            <div x-show="!detecting && detected === true && !({{ $maxFaces }} > 0 && faceCount > {{ $maxFaces }})" x-cloak class="flex items-center justify-center gap-2 text-sm text-green-600 font-semibold">
                <span aria-hidden="true">✓</span>
                <span x-text="faceCount + ' {{ __('ui.faces_detected_label') }}'"></span>
            </div>
            <div x-show="!detecting && detected === false" x-cloak class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 text-center">
                {{ __('ui.no_face_detected') }}
            </div>
            {{-- Max faces warning --}}
            @if($maxFaces > 0)
            <div x-show="!detecting && detected === true && faceCount > {{ $maxFaces }}" x-cloak class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 text-center">
                {{ __('ui.max_faces_error', ['max' => $maxFaces]) }}
            </div>
            @endif
        </div>
        @endif

        {{-- Photo tips per category --}}
        @if(in_array($styleCategory, ['professional', 'thematic', 'adventure']))
        <div class="max-w-lg mx-auto">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700 text-center space-y-1">
                @if($maxFaces === 1)
                <p class="font-semibold">{{ __('ui.single_person_notice') }}</p>
                @endif
                <p>{{ __("ui.photo_tip_{$styleCategory}") }}</p>
            </div>
        </div>
        @endif

        {{-- Orientation selector — visible when photo is ready --}}
        @if($photoReady)
        <div class="max-w-lg mx-auto">
            <p class="font-serif text-sm font-semibold text-pmf-text mb-3 text-center">{{ __('ui.orientation_title') }}</p>
            {{-- 4 columnas fijas dejaban ~35px útiles por celda a 320px: las
                 etiquetas rompían a 3-4 líneas en un paso obligatorio. --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {{-- Auto --}}
                <label class="cursor-pointer">
                    <input type="radio" wire:model="orientation" value="auto" class="sr-only peer">
                    <div class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition
                        peer-checked:border-burgundy peer-checked:bg-burgundy-soft
                        border-pmf-border bg-white hover:border-burgundy/50">
                        <svg class="w-8 h-10 text-pmf-secondary" viewBox="0 0 32 40" fill="none">
                            <rect x="2" y="2" width="28" height="36" rx="3" stroke="currentColor" stroke-width="2" stroke-dasharray="4 3"/>
                            <circle cx="16" cy="20" r="4" fill="currentColor" opacity="0.4"/>
                        </svg>
                        <span class="text-xs font-semibold text-pmf-text">{{ __('ui.orientation_auto') }}</span>
                        <span class="text-[10px] text-pmf-muted">{{ __('ui.orientation_auto_desc') }}</span>
                    </div>
                </label>

                {{-- Vertical --}}
                <label class="cursor-pointer">
                    <input type="radio" wire:model="orientation" value="vertical" class="sr-only peer">
                    <div class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition
                        peer-checked:border-burgundy peer-checked:bg-burgundy-soft
                        border-pmf-border bg-white hover:border-burgundy/50">
                        <svg class="w-7 h-10 text-pmf-secondary" viewBox="0 0 28 40" fill="none">
                            <rect x="2" y="1" width="24" height="38" rx="3" stroke="currentColor" stroke-width="2"/>
                            <rect x="2" y="1" width="24" height="38" rx="3" fill="currentColor" opacity="0.06"/>
                        </svg>
                        <span class="text-xs font-semibold text-pmf-text">{{ __('ui.orientation_vertical') }}</span>
                        <span class="text-[10px] text-pmf-muted">{{ __('ui.orientation_vertical_desc') }}</span>
                    </div>
                </label>

                {{-- Horizontal --}}
                <label class="cursor-pointer">
                    <input type="radio" wire:model="orientation" value="horizontal" class="sr-only peer">
                    <div class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition
                        peer-checked:border-burgundy peer-checked:bg-burgundy-soft
                        border-pmf-border bg-white hover:border-burgundy/50">
                        <svg class="w-10 h-8 mt-1 text-pmf-secondary" viewBox="0 0 40 28" fill="none">
                            <rect x="1" y="2" width="38" height="24" rx="3" stroke="currentColor" stroke-width="2"/>
                            <rect x="1" y="2" width="38" height="24" rx="3" fill="currentColor" opacity="0.06"/>
                        </svg>
                        <span class="text-xs font-semibold text-pmf-text mt-1">{{ __('ui.orientation_horizontal') }}</span>
                        <span class="text-[10px] text-pmf-muted">{{ __('ui.orientation_horizontal_desc') }}</span>
                    </div>
                </label>

                {{-- 1:1 Cuadrado --}}
                <label class="cursor-pointer">
                    <input type="radio" wire:model="orientation" value="square" class="sr-only peer">
                    <div class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition
                        peer-checked:border-burgundy peer-checked:bg-burgundy-soft
                        border-pmf-border bg-white hover:border-burgundy/50">
                        <svg class="w-9 h-9 text-pmf-secondary" viewBox="0 0 36 36" fill="none">
                            <rect x="2" y="2" width="32" height="32" rx="3" stroke="currentColor" stroke-width="2"/>
                            <rect x="2" y="2" width="32" height="32" rx="3" fill="currentColor" opacity="0.06"/>
                        </svg>
                        <span class="text-xs font-semibold text-pmf-text">{{ __('ui.orientation_square') }}</span>
                        <span class="text-[10px] text-pmf-muted">{{ __('ui.orientation_square_desc') }}</span>
                    </div>
                </label>
            </div>
        </div>
        @endif

        {{-- Email — visible si showEmail está activo y la foto está lista --}}
        @if($photoReady && $showEmail)
        <div class="max-w-lg mx-auto space-y-4 bg-white rounded-2xl p-6 shadow-sm border border-pmf-border">
            <div class="flex items-center gap-3 justify-center">
                <div class="w-10 h-10 rounded-full bg-burgundy-soft flex items-center justify-center text-lg">📧</div>
                <div class="text-left">
                    @if($emailRequired)
                        <p class="font-serif font-semibold text-pmf-text text-sm">{{ __('ui.email_required_title') }}</p>
                        <p class="text-xs text-pmf-muted">{{ __('ui.email_required_desc') }}</p>
                    @else
                        <p class="font-serif font-semibold text-pmf-text text-sm">{{ __('ui.email_optional_title') }}</p>
                        <p class="text-xs text-pmf-muted">{{ __('ui.email_optional_desc') }}</p>
                    @endif
                </div>
            </div>
            {{-- El placeholder no es una etiqueta: desaparece al escribir y los
                 lectores de pantalla no lo anuncian de forma fiable.
                 text-center retirado: hacía saltar el cursor al teclear. --}}
            <label for="pu-email" class="sr-only">{{ __('ui.a11y_email_label') }}</label>
            <input
                type="email"
                id="pu-email"
                wire:model="email"
                wire:keydown.enter="submit"
                autocomplete="email"
                placeholder="{{ __('ui.email_placeholder') }}"
                @error('email') aria-invalid="true" aria-describedby="pu-email-error" @enderror
                class="w-full px-4 py-3 rounded-lg bg-champagne border border-pmf-border text-pmf-text font-serif focus:ring-burgundy focus:border-burgundy"
            >
            @error('email') <p id="pu-email-error" role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        @endif

        {{-- Botón submit — visible cuando hay foto Y cara detectada --}}
        @if($photoReady)
        <div class="max-w-lg mx-auto">
            <button
                wire:click="submit"
                wire:loading.attr="disabled"
                x-bind:disabled="({{ $faceDetectionEnabled ? 'true' : 'false' }} && (!detected || detecting)) || ({{ $maxFaces }} > 0 && faceCount > {{ $maxFaces }})"
                class="w-full bg-burgundy text-white font-bold py-3.5 rounded-lg hover:bg-burgundy-dark transition tracking-wide disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="submit">{{ __('ui.upload_btn') }}</span>
                <span wire:loading wire:target="submit">{{ __('ui.upload_btn_loading') }}</span>
            </button>
            @if($showEmail)
            <p class="text-xs text-pmf-muted text-center mt-3">{{ __('ui.email_micro') }}</p>
            @endif
        </div>
        @endif

        {{-- Privacidad: cierra el paso. Es lo que más pesa al decidir subir una
             foto de tu familia, así que se lee entero, no a text-xs. --}}
        <p class="max-w-lg mx-auto text-xs text-pmf-muted leading-relaxed text-left">
            {{ __('ui.upload_privacy') }}
        </p>
    </div>

    {{-- STEP: Processing — generando, ofrecer email si no lo dio --}}
    @elseif($step === 'processing')
    {{-- wire:poll cambia este bloque solo cada 3s; sin aria-live el usuario de
         lector de pantalla se queda sin saber que su retrato está listo. --}}
    <div wire:poll.3s="checkStatus" class="text-center space-y-8 py-8 max-w-lg mx-auto" aria-live="polite">
        <div class="relative w-20 h-20 mx-auto">
            <div class="w-20 h-20 rounded-full bg-burgundy-soft flex items-center justify-center text-3xl">🎨</div>
            <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-burgundy flex items-center justify-center">
                <svg class="w-4 h-4 text-white animate-spin" aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 60" stroke-linecap="round"/></svg>
            </div>
        </div>

        <h1 class="font-serif text-3xl font-bold text-pmf-text">{{ __('ui.processing_title') }}</h1>
        <p class="font-serif text-pmf-secondary leading-relaxed">{{ __('ui.processing_desc') }}</p>

        {{-- Indeterminada: el job de IA no reporta progreso, así que no hay
             porcentaje honesto que dar. Antes decía 65% fijo durante 60s.
             w-80 (320px fijos) desbordaba el viewport de 320px con px-6. --}}
        <div class="w-full max-w-xs mx-auto h-1.5 bg-pmf-border rounded-full overflow-hidden progress-indeterminate"
             role="progressbar" aria-valuetext="{{ __('ui.processing_title') }}">
            <div class="h-full w-2/5 bg-burgundy rounded-full progress-indeterminate__bar"></div>
        </div>

        <span class="inline-flex items-center gap-2 bg-burgundy text-white text-sm font-bold px-6 py-2.5 rounded-full tracking-wide">
            <svg class="w-4 h-4 animate-spin" aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 60" stroke-linecap="round"/></svg>
            {{ __('ui.processing_time') }}
        </span>

        {{-- Email capture durante polling —  solo si showEmail activo y no lo dio antes --}}
        @if($showEmail && !$emailCaptured)
        <div class="max-w-sm mx-auto bg-white rounded-2xl shadow-sm border border-pmf-border p-5 space-y-3">
            <div class="flex items-center gap-2 justify-center">
                <span class="text-lg">📧</span>
                <p class="font-serif font-semibold text-pmf-text text-sm">{{ __('ui.email_polling_title') }}</p>
            </div>
            <p class="text-xs text-pmf-muted leading-relaxed">{{ __('ui.email_polling_desc') }}</p>
            <div class="flex gap-2">
                <label for="pu-email-poll" class="sr-only">{{ __('ui.a11y_email_label') }}</label>
                <input
                    type="email"
                    id="pu-email-poll"
                    wire:model="email"
                    wire:keydown.enter="captureEmailDuringPolling"
                    autocomplete="email"
                    placeholder="{{ __('ui.email_placeholder') }}"
                    class="flex-1 px-3 py-2.5 rounded-lg bg-champagne border border-pmf-border text-pmf-text font-serif focus:ring-burgundy focus:border-burgundy text-sm"
                >
                <button
                    wire:click="captureEmailDuringPolling"
                    wire:loading.attr="disabled"
                    class="bg-burgundy text-white font-semibold px-4 py-2.5 rounded-lg hover:bg-burgundy-dark transition text-sm disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="captureEmailDuringPolling">{{ __('ui.email_polling_btn') }}</span>
                    <span wire:loading wire:target="captureEmailDuringPolling">...</span>
                </button>
            </div>
            @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        @elseif($emailCaptured)
        <div class="max-w-sm mx-auto bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <p class="text-sm text-green-700 font-semibold">
                ✓ {{ __('ui.email_polling_confirmed', ['email' => $email]) }}
            </p>
        </div>
        @endif

        {{-- Si esperando y tiene email: mensaje informativo --}}
        <p class="font-serif text-xs text-pmf-muted">
            {{ __('ui.processing_wait_or_leave') }}
        </p>

        <div class="max-w-md mx-auto bg-white rounded-xl shadow-sm p-8 text-center space-y-4">
            <div class="text-copper text-lg">★★★★★</div>
            <p class="font-serif text-sm text-pmf-text italic leading-relaxed">
                {{ __('ui.processing_testimonial') }}
            </p>
            <div class="w-10 h-0.5 bg-burgundy mx-auto"></div>
            <p class="text-xs text-burgundy font-semibold tracking-wide">
                {{ __('ui.processing_testimonial_author') }}
            </p>
        </div>
    </div>

    {{-- STEP: PIN verification — email obligatorio + PIN --}}
    @elseif($step === 'pin_verify')
    <div class="text-center space-y-8 py-8 max-w-lg mx-auto">
        <div class="relative w-20 h-20 mx-auto">
            <div class="w-20 h-20 rounded-full bg-burgundy-soft flex items-center justify-center text-3xl">🔐</div>
        </div>

        <div>
            <h1 class="font-serif text-3xl font-bold text-pmf-text">
                {{ __('ui.pin_verify_title') }}
            </h1>
            <p class="font-serif text-pmf-secondary mt-2">
                {{ __('ui.pin_verify_subtitle') }}
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-pmf-border p-6 space-y-4">
            <div class="flex items-center gap-3 justify-center">
                <span class="text-2xl">📧</span>
                <p class="font-serif font-semibold text-pmf-text">
                    {{ __('ui.pin_verify_sent_to') }}
                </p>
            </div>
            <p class="font-mono text-lg font-bold text-burgundy">{{ $email }}</p>

            <label for="pu-pin" class="sr-only">{{ __('ui.a11y_pin_label') }}</label>
            <input
                type="text"
                id="pu-pin"
                wire:model="pin"
                wire:keydown.enter="verifyPin"
                autocomplete="one-time-code"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]*"
                placeholder="000000"
                class="w-40 mx-auto block px-4 py-3 rounded-lg bg-champagne border border-pmf-border text-pmf-text font-mono text-2xl font-bold text-center tracking-[0.3em] focus:ring-burgundy focus:border-burgundy"
                autofocus
            >
            @error('pin') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <button
                wire:click="verifyPin"
                wire:loading.attr="disabled"
                class="w-full bg-burgundy text-white font-bold py-3.5 rounded-lg hover:bg-burgundy-dark transition tracking-wide disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="verifyPin">{{ __('ui.pin_verify_btn') }}</span>
                <span wire:loading wire:target="verifyPin">{{ __('ui.upload_btn_loading') }}</span>
            </button>

            <p class="text-xs text-pmf-muted">
                {{ __('ui.pin_verify_not_received') }}
                {{-- Antes: sin loading, sin disabled y sin éxito. El usuario
                     pulsaba y no pasaba nada visible, así que lo pulsaba 5 veces. --}}
                <button
                    wire:click="resendPin"
                    wire:loading.attr="disabled"
                    wire:target="resendPin"
                    class="text-burgundy font-semibold hover:underline active:opacity-70 disabled:opacity-50 disabled:cursor-not-allowed disabled:no-underline"
                >
                    <span wire:loading.remove wire:target="resendPin">{{ __('ui.pin_verify_resend') }}</span>
                    <span wire:loading wire:target="resendPin">{{ __('ui.upload_btn_loading') }}</span>
                </button>
            </p>
            @if($pinResent)
                <p role="status" class="text-sm text-green-700 font-semibold">{{ __('ui.a11y_resent') }}</p>
            @endif
        </div>

        <div class="space-y-2 text-sm text-pmf-muted">
            <p>💡 {{ __('ui.email_sent_spam') }}</p>
            <p>🔒 {{ __('ui.email_sent_protected') }}</p>
        </div>
    </div>

    {{-- STEP legacy: email_sent (por si quedaba alguna sesión antigua) --}}
    @elseif($step === 'email_sent')
    <div class="text-center space-y-8 py-8 max-w-lg mx-auto">
        <div class="relative w-20 h-20 mx-auto">
            <div class="w-20 h-20 rounded-full bg-burgundy-soft flex items-center justify-center text-3xl">🎨</div>
            <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                <svg class="w-4 h-4 text-white animate-spin" aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40 60" stroke-linecap="round"/></svg>
            </div>
        </div>
        <h1 class="font-serif text-3xl font-bold text-pmf-text">{{ __('ui.email_sent_title') }}</h1>
        <p class="font-serif text-pmf-secondary mt-2">{{ __('ui.email_sent_subtitle') }}</p>
        <div class="bg-white rounded-2xl shadow-sm border border-pmf-border p-6 space-y-4">
            <p class="font-mono text-lg font-bold text-burgundy">{{ $email }}</p>
            <p class="font-serif text-sm text-pmf-secondary leading-relaxed">{{ __('ui.email_sent_instructions') }}</p>
        </div>
    </div>
    @endif

<script src="{{ asset('js/face-api.min.js') }}"></script>
<script>
function faceDetector() {
    return {
        dragging: false,
        detecting: false,
        detected: null,
        faceCount: 0,
        modelReady: false,
        localPreviewUrl: null,
        fileName: '',

        async loadModel() {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                this.modelReady = true;
            } catch (e) {
                console.warn('Face detection model failed to load:', e);
                this.detected = true;
                this.faceCount = 1;
                @this.set('faceDetected', true);
                @this.set('faceCount', 1);
            }
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Preview local instantáneo (sin subir/descargar del servidor)
            if (this.localPreviewUrl) URL.revokeObjectURL(this.localPreviewUrl);
            this.localPreviewUrl = URL.createObjectURL(file);
            this.fileName = file.name;

            // Face detection inmediata desde el archivo local
            this.detectFaces(file);
        },

        async detectFaces(file) {
            if (!this.modelReady) {
                this.detected = true;
                @this.set('faceDetected', true);
                return;
            }

            // Si no se pasa file, intentar desde el input
            if (!file) {
                const fileInput = this.$refs.fileInput;
                if (!fileInput || !fileInput.files[0]) return;
                file = fileInput.files[0];
            }

            this.detecting = true;
            this.detected = null;

            try {
                const fullImg = await this.loadImageFromFile(file);

                const detections = await faceapi.detectAllFaces(
                    fullImg,
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 512, scoreThreshold: 0.15 })
                );

                this.faceCount = detections.length;
                this.detected = detections.length > 0;

                @this.set('faceDetected', this.detected);
                @this.set('faceCount', this.faceCount);
            } catch (e) {
                console.warn('Face detection error:', e);
                this.detected = true;
                @this.set('faceDetected', true);
            } finally {
                this.detecting = false;
            }
        },

        loadImageFromFile(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = URL.createObjectURL(file);
            });
        }
    }
}
</script>
</div>
