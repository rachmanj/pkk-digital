@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $authType = $authType ?? 'login';
    $dashboardUrl = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'home');
    $dashboardUrl = $layoutHelper->makeUrl($dashboardUrl);

    $bodyClasses = "{$authType}-page bg-body-secondary";
@endphp

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('classes_body'){{ $bodyClasses }}@stop

@section('body')
    <main class="{{ $authType }}-box">

        <h1 class="{{ $authType }}-logo">
            <a href="{{ $dashboardUrl }}">
                {!! config('adminlte.logo', 'Buku PKK Digital') !!}
            </a>
        </h1>

        <div class="card {{ config('adminlte.classes_auth_card', 'card-outline card-primary') }}">

            @hasSection('auth_header')
                <div class="card-header {{ config('adminlte.classes_auth_header', '') }}">
                    <h3 class="card-title float-none text-center">
                        @yield('auth_header')
                    </h3>
                </div>
            @endif

            <div class="card-body {{ $authType }}-card-body {{ config('adminlte.classes_auth_body', '') }}">
                @yield('auth_body')
            </div>

            @hasSection('auth_footer')
                <div class="card-footer {{ config('adminlte.classes_auth_footer', '') }}">
                    @yield('auth_footer')
                </div>
            @endif

        </div>

    </main>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
