@extends('adminlte::page')

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
    @include('partials.kelurahan-switcher')
    @yield('page_content')
@stop
