@extends('layouts.app', [
    'title' => 'Data Anggota — Buku PKK Digital',
    'header' => 'Data Anggota',
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
            <form method="get" action="{{ route('orang.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="q">Cari nama / alamat</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm"
                        value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="pokja_id">Pokja</label>
                    <select name="pokja_id" id="pokja_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($pokjaList as $pokja)
                            <option value="{{ $pokja->id }}" @selected((string) $filters['pokja_id'] === (string) $pokja->id)>
                                {{ $pokja->kode }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="jenis">Jenis keanggotaan</label>
                    <select name="jenis" id="jenis" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="tp_pkk" @selected($filters['jenis'] === 'tp_pkk')>TP PKK</option>
                        <option value="kader_umum" @selected($filters['jenis'] === 'kader_umum')>Kader Umum</option>
                        <option value="kader_khusus" @selected($filters['jenis'] === 'kader_khusus')>Kader Khusus</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="aktif">Status aktif</label>
                    <select name="aktif" id="aktif" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="1" @selected($filters['aktif'] === '1')>Aktif</option>
                        <option value="0" @selected($filters['aktif'] === '0')>Tidak aktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('orang.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <a href="{{ route('orang.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus"></i> Tambah Anggota
        </a>
        <a href="{{ route('orang.daftar-anggota') }}" class="btn btn-outline-primary btn-sm">
            Daftar Anggota TP PKK dan Kader
        </a>
        @include('partials.cetak-toolbar', ['kodeBuku' => 'daftar_anggota'])
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>L/P</th>
                        <th>Tempat Lahir</th>
                        <th>Tgl. Lahir</th>
                        <th>Umur</th>
                        <th>Status</th>
                        <th>Alamat</th>
                        <th>Pendidikan</th>
                        <th>Pekerjaan</th>
                        <th>Keterangan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orang as $item)
                        @php
                            $keanggotaanAktif = $item->keanggotaan->firstWhere('is_aktif', true) ?? $item->keanggotaan->first();
                        @endphp
                        <tr>
                            <td>{{ $orang->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('orang.show', $item) }}">{{ $item->nama }}</a>
                            </td>
                            <td>{{ $keanggotaanAktif?->jabatan ?: '—' }}</td>
                            <td>{{ $item->jenis_kelamin ?: '—' }}</td>
                            <td>{{ $item->tempat_lahir ?: '—' }}</td>
                            <td>{{ $item->tanggal_lahir?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $item->umur ?? '—' }}</td>
                            <td>{{ $item->status_perkawinan ?: '—' }}</td>
                            <td>{{ $item->alamat ?: '—' }}</td>
                            <td>{{ $item->pendidikan ?: '—' }}</td>
                            <td>{{ $item->pekerjaan ?: '—' }}</td>
                            <td>{{ $item->catatan ?: '—' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('orang.edit', $item) }}" class="btn btn-xs btn-outline-secondary btn-sm py-0">Ubah</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">Belum ada data anggota.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orang->hasPages())
            <div class="card-footer py-2">
                {{ $orang->links() }}
            </div>
        @endif
    </div>
@endsection
