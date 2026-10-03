@extends('layouts.app', [
    'title' => $orang->nama . ' — Buku PKK Digital',
    'header' => 'Profil Anggota',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    @if ($orang->foto_path)
                        <img src="{{ route('orang.foto', $orang) }}" alt="Foto {{ $orang->nama }}"
                            class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 120px; height: 120px; font-size: 2.5rem;">
                            {{ $orang->inisialNama() }}
                        </div>
                    @endif
                    <h4 class="mb-1">{{ $orang->nama }}</h4>
                    <p class="text-muted mb-0">{{ $orang->jenis_kelamin === 'L' ? 'Laki-laki' : ($orang->jenis_kelamin === 'P' ? 'Perempuan' : '—') }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Data Pribadi</h3>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('orang.edit', $orang) }}" class="btn btn-outline-primary">Ubah</a>
                        <form method="post" action="{{ route('orang.destroy', $orang) }}" class="d-inline"
                            onsubmit="return confirm('Hapus data anggota ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Hapus</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tempat / Tgl. Lahir</dt>
                        <dd class="col-sm-8">
                            {{ $orang->tempat_lahir ?: '—' }},
                            {{ $orang->tanggal_lahir?->format('d F Y') ?? '—' }}
                            @if ($orang->umur !== null)
                                ({{ $orang->umur }} tahun)
                            @endif
                        </dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">{{ $orang->status_perkawinan ?: '—' }}</dd>
                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $orang->alamat ?: '—' }}</dd>
                        <dt class="col-sm-4">RT</dt>
                        <dd class="col-sm-8">
                            @if ($orang->rt)
                                RT {{ $orang->rt->nomor }}@if($orang->rt->dasawisma) — {{ $orang->rt->dasawisma }}@endif
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-sm-4">Pendidikan</dt>
                        <dd class="col-sm-8">{{ $orang->pendidikan ?: '—' }}</dd>
                        <dt class="col-sm-4">Pekerjaan</dt>
                        <dd class="col-sm-8">{{ $orang->pekerjaan ?: '—' }}</dd>
                        <dt class="col-sm-4">No. HP</dt>
                        <dd class="col-sm-8">{{ $orang->no_hp ?: '—' }}</dd>
                        <dt class="col-sm-4">Keterangan</dt>
                        <dd class="col-sm-8">{{ $orang->catatan ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Keanggotaan</h3>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Pokja</th>
                        <th>Jabatan</th>
                        <th>No. Registrasi</th>
                        <th>SK</th>
                        <th>Periode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orang->keanggotaan as $k)
                        <tr>
                            <td>{{ $k->labelJenis() }}</td>
                            <td>{{ $k->pokja?->nama ?? '—' }}</td>
                            <td>{{ $k->jabatan ?: '—' }}</td>
                            <td>{{ $k->no_registrasi ?: '—' }}</td>
                            <td>{{ $k->sk_nomor ?: '—' }}</td>
                            <td>
                                {{ $k->mulai?->format('d/m/Y') ?? '—' }}
                                —
                                {{ $k->selesai?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td>{{ $k->is_aktif ? 'Aktif' : 'Tidak aktif' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada keanggotaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('orang.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke daftar</a>
    </div>
@endsection
