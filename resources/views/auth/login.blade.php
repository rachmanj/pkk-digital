@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $loginUrl = $layoutHelper->makeUrl(route('login'));
@endphp

@section('title', 'Masuk — Buku PKK Digital')

@section('auth_header', 'Masuk')

@section('auth_body')
    <form action="{{ $loginUrl }}" method="post">
        @csrf

        <label for="email" class="visually-hidden">Email</label>
        <div class="input-group mb-3">
            <input type="email" name="email" id="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="Email" autofocus>

            <div class="input-group-text">
                <span class="bi bi-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <label for="password" class="visually-hidden">Kata sandi</label>
        <div class="input-group mb-3">
            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Kata sandi">

            <div class="input-group-text">
                <span class="bi bi-lock-fill {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="d-grid">
            <button type="submit" class="btn {{ config('adminlte.classes_auth_btn', 'btn-primary') }}">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Masuk
            </button>
        </div>
    </form>
@stop

@section('auth_footer')
@stop
