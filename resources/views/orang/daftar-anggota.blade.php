@extends('layouts.app', [
    'title' => 'Daftar Anggota TP PKK dan Kader — Buku PKK Digital',
    'header' => 'Daftar Anggota TP PKK dan Kader',
])

@section('page_content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="get" action="{{ route('orang.daftar-anggota') }}" class="d-flex gap-2">
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama / alamat"
                value="{{ $filters['q'] }}">
            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
        </form>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('orang.index') }}" class="btn btn-outline-secondary btn-sm">Data Anggota (format buku 1)</a>
            @include('partials.cetak-toolbar', ['kodeBuku' => 'daftar_anggota_tp_pkk'])
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-bordered table-striped mb-0 align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">No. Registrasi TP PKK</th>
                        <th rowspan="2">Nama</th>
                        <th rowspan="2">L/P</th>
                        <th colspan="3">Kedudukan / Fungsi</th>
                        <th rowspan="2">Tgl. Lahir</th>
                        <th rowspan="2">Umur</th>
                        <th rowspan="2">Status</th>
                        <th rowspan="2">Alamat</th>
                        <th rowspan="2">Pendidikan</th>
                        <th rowspan="2">Pekerjaan</th>
                        <th rowspan="2">Keterangan</th>
                    </tr>
                    <tr>
                        <th>TP PKK</th>
                        <th>Kader Umum</th>
                        <th>Kader Khusus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orang as $item)
                        @php
                            $aktifKeanggotaan = $item->keanggotaan->where('is_aktif', true);
                            $noReg = $item->keanggotaan
                                ->first(fn ($k) => $k->no_registrasi !== null)
                                ?->no_registrasi;
                            $hasTp = $aktifKeanggotaan->contains('jenis', \App\Models\Keanggotaan::JENIS_TP_PKK);
                            $hasUmum = $aktifKeanggotaan->contains('jenis', \App\Models\Keanggotaan::JENIS_KADER_UMUM);
                            $hasKhusus = $aktifKeanggotaan->contains('jenis', \App\Models\Keanggotaan::JENIS_KADER_KHUSUS);
                        @endphp
                        <tr>
                            <td>{{ $orang->firstItem() + $loop->index }}</td>
                            <td>{{ $noReg ?: '—' }}</td>
                            <td class="text-start">
                                <a href="{{ route('orang.show', $item) }}">{{ $item->nama }}</a>
                            </td>
                            <td>{{ $item->jenis_kelamin ?: '—' }}</td>
                            <td>@if($hasTp)<i class="bi bi-check-lg text-success"></i>@else—@endif</td>
                            <td>@if($hasUmum)<i class="bi bi-check-lg text-success"></i>@else—@endif</td>
                            <td>@if($hasKhusus)<i class="bi bi-check-lg text-success"></i>@else—@endif</td>
                            <td>{{ $item->tanggal_lahir?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $item->umur ?? '—' }}</td>
                            <td>{{ $item->status_perkawinan ?: '—' }}</td>
                            <td class="text-start">{{ $item->alamat ?: '—' }}</td>
                            <td>{{ $item->pendidikan ?: '—' }}</td>
                            <td>{{ $item->pekerjaan ?: '—' }}</td>
                            <td class="text-start">{{ $item->catatan ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-muted py-4">Belum ada data.</td>
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
