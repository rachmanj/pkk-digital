@extends('layouts.app', [
    'title' => 'Program Kerja — Buku PKK Digital',
    'header' => 'Program Kerja',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('program-kerja.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="buku">Unit</label>
                    <select name="buku" id="buku" class="form-select form-select-sm">
                        <option value="kelurahan" @selected($filters['buku'] === 'kelurahan')>Kelurahan</option>
                        @foreach ($pokjaList as $pokja)
                            <option value="pokja-{{ $pokja->id }}" @selected($filters['buku'] === 'pokja-'.$pokja->id)>
                                Pokja {{ $pokja->kode }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="tahun">Tahun</label>
                    <input type="number" name="tahun" id="tahun" class="form-control form-control-sm"
                        min="2000" max="2100" value="{{ $filters['tahun'] }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="q">Cari kegiatan / program</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm" value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('program-kerja.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        @can(\App\Support\PkkPermission::KELOLA_PROGRAM_KERJA)
            <a href="{{ route('program-kerja.create', ['buku' => $filters['buku'], 'tahun' => $filters['tahun']]) }}"
                class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Program Kerja
            </a>
        @endcan
        @include('partials.cetak-toolbar', ['kodeBuku' => 'program_kerja'])
        @include('partials.cetak-toolbar', [
            'kodeBuku' => 'program_kerja_matriks',
            'toolbarLabel' => 'Matriks',
        ])
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NO.</th>
                        <th>PROGRAM</th>
                        <th>KEGIATAN</th>
                        <th>TGL.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->kode ?: '—' }}</td>
                            <td>{{ $item->program ?: '—' }}</td>
                            <td>{{ $item->kegiatan }}</td>
                            <td>{{ $item->tanggal_kegiatan?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('program-kerja.show', $item) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada data program kerja.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())
            <div class="card-footer">{{ $items->links() }}</div>
        @endif
    </div>
@endsection
