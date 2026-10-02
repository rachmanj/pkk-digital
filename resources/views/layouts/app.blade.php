@extends('adminlte::page')

@section('title', $title ?? 'Buku PKK Digital')

@section('content_header')
    @isset($header)
        <h1 class="m-0 text-body">{{ $header }}</h1>
    @endisset
@stop

@section('content')
    @yield('page_content')
@stop
