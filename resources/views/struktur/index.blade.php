@extends('layouts.app', [
    'title' => 'Struktur Pengurus — Buku PKK Digital',
    'header' => 'Struktur Pengurus',
])

@section('page_content')
    @php
        use App\Models\StrukturPengurus;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @if ($canKelola)
            <a href="{{ route('struktur.create', ['unit' => $filters['unit'] ?: StrukturPengurus::UNIT_TP_PKK]) }}" class="btn btn-primary btn-sm">
                Tambah Pengurus
            </a>
        @endif
        <a href="{{ route('cetak.show', ['buku' => 'struktur_pkk', 'tahun' => now()->year]) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            Cetak TP PKK
        </a>
        <a href="{{ route('cetak.show', ['buku' => 'struktur_lbs', 'tahun' => now()->year]) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            Cetak LBS
        </a>
        <a href="{{ route('export.buku', ['buku' => 'struktur_pkk', 'tahun' => now()->year]) }}" class="btn btn-outline-success btn-sm">
            Export TP PKK
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('struktur.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="unit">Filter unit</label>
                    <select name="unit" id="unit" class="form-select form-select-sm">
                        <option value="">Semua unit</option>
                        @foreach (StrukturPengurus::unitNilai() as $nilai)
                            <option value="{{ $nilai }}" @selected($filters['unit'] === $nilai)>
                                {{ StrukturPengurus::labelUnit($nilai) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @forelse ($perUnit as $unit => $kelompok)
        <div class="card mb-3">
            <div class="card-header fw-semibold">{{ StrukturPengurus::labelUnit($unit) }}</div>
            <div class="card-body">
                @foreach ($kelompok['per_jabatan'] as $jabatan => $anggota)
                    <h6 class="text-uppercase mt-2 mb-1">{{ $jabatan }}</h6>
                    <ul class="list-unstyled ms-2 mb-2">
                        @foreach ($anggota as $row)
                            <li class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <a href="{{ route('struktur.show', $row) }}">{{ $row->nama }}</a>
                                @if ($row->rt)
                                    <span class="text-muted small">RT {{ $row->rt }}</span>
                                @endif
                                @if ($canKelola)
                                    <form method="post" action="{{ route('struktur.naik', $row) }}" class="d-inline">@csrf<button type="submit" class="btn btn-link btn-sm p-0">↑</button></form>
                                    <form method="post" action="{{ route('struktur.turun', $row) }}" class="d-inline">@csrf<button type="submit" class="btn btn-link btn-sm p-0">↓</button></form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endforeach

                @if ($unit === StrukturPengurus::UNIT_POKJA || $kelompok['per_pokja']->isNotEmpty())
                    <h6 class="mt-3">Per Pokja</h6>
                    @foreach ($kelompok['per_pokja'] as $pokjaId => $anggota)
                        @php
                            $pokja = $anggota->first()?->pokja;
                        @endphp
                        <div class="ms-2 mb-2">
                            <strong>{{ $pokja ? 'Pokja '.$pokja->kode : 'Pokja' }}</strong>
                            <ul class="list-unstyled ms-2">
                                @foreach ($anggota as $row)
                                    <li><a href="{{ route('struktur.show', $row) }}">{{ $row->jabatan }} — {{ $row->nama }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">Belum ada data struktur pengurus untuk kelurahan ini.</div>
    @endforelse
@endsection
