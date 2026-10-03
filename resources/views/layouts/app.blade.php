@extends('adminlte::page')

@section('meta_tags')
    @include('partials.pwa-head')
@endsection

@section('content_top_nav_right')
    @include('partials.pwa-install-ui')
@endsection

@section('js')
    <script src="{{ asset('js/pwa.js') }}" defer></script>
@endsection

@section('title', $title ?? 'Buku PKK Digital')

@section('content_header')
    @isset($header)
        <h1 class="m-0 text-body">{{ $header }}</h1>
    @endisset
@stop

@section('footer')
    <strong>TP PKK Kelurahan Gunung Sari Ilir</strong>
@stop

@section('content')
    @include('partials.pwa-ios-hint')
    @include('partials.kelurahan-switcher')
    @yield('page_content')
@stop
