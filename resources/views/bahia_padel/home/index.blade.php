@extends('bahia_padel/home/plantilla')

@section('title_header', 'Bahía Pádel')

@section('contenedor')
{{-- Video a ancho completo ocupando el lugar del header. Archivo: storage/app/public/videos/home-video.mp4 (public/storage/videos/...) --}}
<section class="home-video-fullwidth">
    <video class="home-video" autoplay muted loop playsinline preload="metadata">
        <source src="{{ \Illuminate\Support\Facades\Storage::url('videos/home-video.mp4') }}" type="video/mp4">
    </video>
</section>

<section class="py-4 page-content-home">
   
</section>
@endsection