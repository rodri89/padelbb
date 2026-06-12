@extends('layouts.carta')

@section('title', 'Carta')

@push('head')
<link href="{{ asset('css/carta/monte.css') }}?v=3" rel="stylesheet">
@endpush

@section('content')
<div class="carta-page">
    <div class="container">

        <!-- Header -->
        <div class="text-center pt-5 pb-3">
            <p class="carta-subtitle mb-2">Cancha de Padel</p>
            <h1 class="carta-main-title mb-2" style="font-size: 42px; line-height: 0.9; letter-spacing: 0.05em;">Bahia Padel</h1>
            <h1 class="carta-main-title mb-2" style="font-size: 36; line-height: 0.9; letter-spacing: 0.05em;">Menu</h1>
            <div class="divider mt-3 mb-2"></div>
            <p class="footer-note mt-2">Carta &middot; {{ now()->format('F Y') }}</p>
        </div>

        <!-- Chips de categorías -->
        @if($items->count())
        <div class="px-3 mb-5">
            <div class="chips-wrapper">
                @foreach($items->keys() as $index => $category)
                    <button onclick="scrollToCategory('cat-{{ $index }}')" class="cat-pill">
                        {{ $category }}
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Menú -->
        <div class="px-3">
            @forelse($items as $category => $products)
                @php $catIndex = $loop->index; @endphp
                <div id="cat-{{ $catIndex }}" style="scroll-margin-top: 90px;" class="mb-5">
                    <h2 class="category-heading">{{ $category }}</h2>
                    <div class="category-line"></div>

                    @foreach($products as $item)
                    <div class="mb-4">
                        <div class="d-flex align-items-baseline" style="gap: 0;">
                            <span class="item-name">{{ $item->name }}</span>
                            <span class="dotted-rule"></span>
                            <span class="item-price">${{ number_format($item->price, 0, ',', '.') }}</span>
                        </div>
                        @if($item->description)
                            <p class="item-desc mt-1 mb-0">{{ $item->description }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            @empty
                <div class="text-center py-5">
                    <h2 class="h4 font-italic" style="color: #999999;">Menú en actualización</h2>
                    <p class="footer-note">Volvé en unos minutos</p>
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pb-4 px-3">
            <div class="divider-wide mb-4"></div>
            <p class="footer-note mb-1">
                Consultá por adicionales y opciones sin TACC
            </p>
            <p class="footer-note mb-0">
                Bahía Padel
            </p>
        </div>
    </div>
</div>

<script>
function scrollToCategory(id) {
    const el = document.getElementById(id);
    if(el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>
@endsection
