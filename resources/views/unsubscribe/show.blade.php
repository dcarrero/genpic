{{-- Hallmark · genre: editorial · family: app · macrostructure: Workbench · design-system: design.md · designed-as-app
     baja de emails --}}
@extends('layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center bg-champagne px-4 py-16">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 text-center">

        <h1 class="font-serif text-2xl font-bold text-burgundy mb-6">
            {{ __('ui.unsubscribe_title') }}
        </h1>

        @if (isset($processed) && $processed && $action === 'resubscribe')
            {{-- Confirmación de re-suscripción --}}
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800">
                    {{ __('ui.unsubscribe_resubscribe') }}
                </p>
            </div>

            <form method="POST" action="{{ URL::signedRoute('unsubscribe.process', ['email' => $email]) }}">
                @csrf
                <input type="hidden" name="action" value="unsubscribe">
                <button type="submit"
                    class="w-full bg-burgundy hover:bg-burgundy-dark text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    {{ __('ui.unsubscribe_button') }}
                </button>
            </form>

        @elseif (!$optedOut)
            {{-- Usuario aún suscrito — mostrar opción de desuscribirse --}}
            <p class="text-gray-700 mb-6 leading-relaxed">
                {{ __('ui.unsubscribe_confirm') }}
            </p>

            <form method="POST" action="{{ URL::signedRoute('unsubscribe.process', ['email' => $email]) }}">
                @csrf
                <input type="hidden" name="action" value="unsubscribe">
                <button type="submit"
                    class="w-full bg-burgundy hover:bg-burgundy-dark text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    {{ __('ui.unsubscribe_button') }}
                </button>
            </form>

        @else
            {{-- Usuario ya desuscrito --}}
            @if (isset($processed) && $processed && $action === 'unsubscribe')
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <p class="text-green-800">
                        {{ __('ui.unsubscribe_done') }}
                    </p>
                </div>
            @endif

            <p class="text-gray-700 mb-4">
                {{ __('ui.unsubscribe_resubscribe') }}
            </p>

            <form method="POST" action="{{ URL::signedRoute('unsubscribe.process', ['email' => $email]) }}">
                @csrf
                <input type="hidden" name="action" value="resubscribe">
                <button type="submit"
                    class="w-full bg-burgundy hover:bg-burgundy-dark text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    {{ __('ui.unsubscribe_resubscribe_button') }}
                </button>
            </form>
        @endif

        <p class="mt-6 text-xs text-gray-500 leading-relaxed">
            {{ __('ui.unsubscribe_note') }}
        </p>
    </div>
</div>
@endsection
