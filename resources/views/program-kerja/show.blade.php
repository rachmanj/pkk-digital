@extends('layouts.app', [
    'title' => 'Detail Program Kerja — Buku PKK Digital',
    'header' => 'Detail Program Kerja',
])

@section('page_content')
    @php
        use App\Models\ProgramKerja;

        $bukuKembali = $programKerja->pokja_id ? 'pokja-'.$programKerja->pokja_id : 'kelurahan';
        $sumberPelaksanaan = $programKerja->sumberBulanPelaksanaan();
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('program-kerja.index', ['buku' => $bukuKembali, 'tahun' => $programKerja->tahun]) }}"
            class="btn btn-outline-secondary btn-sm">
            Kembali ke daftar
        </a>
        @can(\App\Support\PkkPermission::KELOLA_PROGRAM_KERJA)
            <a href="{{ route('program-kerja.edit', $programKerja) }}" class="btn btn-primary btn-sm">Ubah</a>
            <form method="post" action="{{ route('program-kerja.destroy', $programKerja) }}"
                onsubmit="return confirm('Hapus butir program kerja ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
        @endcan
    </div>

    <div class="card mb-3">
        <div class="card-header">
            {{ $programKerja->kegiatan }} — {{ $programKerja->tahun }} — {{ $programKerja->labelUnit() }}
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">No. / Kode</dt>
                <dd class="col-sm-9">{{ $programKerja->kode ?: '—' }}</dd>
                <dt class="col-sm-3">Program</dt>
                <dd class="col-sm-9">{{ $programKerja->program ?: '—' }}</dd>
                <dt class="col-sm-3">Kegiatan</dt>
                <dd class="col-sm-9">{{ $programKerja->kegiatan }}</dd>
                <dt class="col-sm-3">Tanggal kegiatan</dt>
                <dd class="col-sm-9">{{ $programKerja->tanggal_kegiatan?->format('d/m/Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Tujuan</dt>
                <dd class="col-sm-9">{{ $programKerja->tujuan ?: '—' }}</dd>
                <dt class="col-sm-3">Sasaran</dt>
                <dd class="col-sm-9">{{ $programKerja->sasaran ?: '—' }}</dd>
                <dt class="col-sm-3">Tempat</dt>
                <dd class="col-sm-9">{{ $programKerja->tempat ?: '—' }}</dd>
                <dt class="col-sm-3">Sumber dana</dt>
                <dd class="col-sm-9">{{ $programKerja->sumber_dana ?: '—' }}</dd>
                <dt class="col-sm-3">Keterangan</dt>
                <dd class="col-sm-9">{{ $programKerja->keterangan ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Matriks perencanaan &amp; pelaksanaan</div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-bordered mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="text-start">Bulan</th>
                        @foreach (ProgramKerja::daftarBulan() as $bulan)
                            <th>{{ $bulan }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="text-start">Perencanaan</th>
                        @foreach (ProgramKerja::daftarBulan() as $bulan)
                            <td>
                                @if ($programKerja->bulanRencanaTerpilih($bulan))
                                    {{ ProgramKerja::TANDA_CENTANG }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="text-start">Pelaksanaan</th>
                        @foreach (ProgramKerja::daftarBulan() as $bulan)
                            @php
                                $sumber = $sumberPelaksanaan[$bulan] ?? null;
                            @endphp
                            <td @class([
                                'bg-warning-subtle' => $sumber === 'manual',
                                'bg-info-subtle' => $sumber === 'kegiatan',
                                'bg-success-subtle' => $sumber === 'keduanya',
                            ])
                                @if ($sumber === 'manual') title="Manual" @elseif ($sumber === 'kegiatan') title="Dari kegiatan tertaut" @elseif ($sumber === 'keduanya') title="Manual dan dari kegiatan" @endif>
                                @if ($sumber !== null)
                                    {{ ProgramKerja::TANDA_CENTANG }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            Legenda pelaksanaan: kuning = manual, biru = dari kegiatan tertaut, hijau = keduanya.
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Realisasi (kegiatan tertaut)</span>
            <span class="badge text-bg-secondary">{{ $jumlahRealisasi }} kegiatan</span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Judul</th>
                        <th>Tanggal</th>
                        <th>Tempat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programKerja->kegiatanTertaut as $kegiatan)
                        <tr>
                            <td>{{ $kegiatan->nama }}</td>
                            <td>{{ $kegiatan->tanggal?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $kegiatan->tempat }}</td>
                            <td>
                                <a href="{{ route('kegiatan.show', $kegiatan) }}" class="btn btn-outline-secondary btn-sm">Buka</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada kegiatan yang ditaut.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
