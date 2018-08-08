@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => 'http://localhost:8000/inicio'])
     <img src="{{ asset('img/bannerCorreo.jpg') }}">
    @endcomponent
@endslot

{{-- Body --}}
    {{ $main }}

{{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

{{-- Button --}}
@component('mail::button', ['url' => $ruta, 'color' => 'blue'])
{{ $boton }}
@endcomponent

{{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }} - Lotería de Santa Fe
        @endcomponent
    @endslot
@endcomponent
