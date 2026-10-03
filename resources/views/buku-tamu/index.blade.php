@extends('layouts.app', [
    'title' => 'Buku Tamu — Buku PKK Digital',
    'header' => 'Buku Tamu',
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
            <form method="get" action="{{ route('buku-tamu.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="buku">Buku</label>
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
                <div class="col-md-4">
                    <label class="form-label" for="q">Cari nama tamu / keperluan / tujuan</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm"
                        value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('buku-tamu.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('buku-tamu.create', ['buku' => $filters['buku']]) }}"
            class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Tamu
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NO</th>
                        <th>TANGGAL</th>
                        <th>NAMA TAMU</th>
                        <th>ALAMAT</th>
                        <th>KEPERLUAN</th>
                        <th>TUJUAN</th>
                        <th>TANDA TANGAN</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukuTamu as $baris)
                        <tr>
                            <td>{{ $baris->no_urut_tahun }}</td>
                            <td>{{ $baris->tanggal?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $baris->nama_tamu }}</td>
                            <td>{{ $baris->alamat ?: '—' }}</td>
                            <td>{{ $baris->keperluan ?: '—' }}</td>
                            <td>{{ $baris->tujuan ?: '—' }}</td>
                            <td>—</td>
                            <td class="text-nowrap">
                                <a href="{{ route('buku-tamu.show', $baris) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada tamu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bukuTamu->hasPages())
            <div class="card-footer py-2">
                {{ $bukuTamu->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
