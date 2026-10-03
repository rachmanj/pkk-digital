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

        <label for="username" class="visually-hidden">Nama pengguna</label>
        <div class="input-group mb-3">
            <input type="text" name="username" id="username"
                class="form-control @error('username') is-invalid @enderror"
                value="{{ old('username') }}" placeholder="Nama pengguna" autocomplete="username" autofocus>

            <div class="input-group-text">
                <span class="bi bi-person-fill {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            @error('username')
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
