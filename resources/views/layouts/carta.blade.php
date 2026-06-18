<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Carta') · La Cantina</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('bahiapadel/iconos/logo_padel_bb.png') }}" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body>
    @yield('content')
</body>
</html>
