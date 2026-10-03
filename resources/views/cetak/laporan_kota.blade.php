@extends('cetak.layout')

@section('isi_cetak')
    @include('laporan-kota.konten', ['dataset' => $dataset])
@endsection
