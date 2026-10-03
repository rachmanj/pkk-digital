@extends('layouts.app', [
    'title' => 'Detail Agenda Surat — Buku PKK Digital',
    'header' => 'Detail Agenda Surat',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('agenda-surat.index', ['jenis' => $agendaSurat->jenis]) }}" class="btn btn-outline-secondary btn-sm">
            Kembali ke daftar
        </a>
        <a href="{{ route('agenda-surat.edit', $agendaSurat) }}" class="btn btn-primary btn-sm">Ubah</a>
        <form method="post" action="{{ route('agenda-surat.destroy', $agendaSurat) }}"
            onsubmit="return confirm('Hapus agenda surat ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
        </form>
        @if ($agendaSurat->file_path)
            <a href="{{ route('agenda-surat.berkas', $agendaSurat) }}" class="btn btn-success btn-sm" target="_blank" rel="noopener">
                Unduh berkas scan
            </a>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Surat {{ $agendaSurat->jenis === 'masuk' ? 'Masuk' : 'Keluar' }}
            — No. urut {{ $agendaSurat->no_urut_tahun }} / {{ $agendaSurat->tanggal_surat?->format('Y') }}
            @if ($agendaSurat->pokja)
                (Pokja {{ $agendaSurat->pokja->kode }})
            @else
                (Kelurahan)
            @endif
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nomor surat</dt>
                <dd class="col-sm-9">{{ $agendaSurat->no_surat }}</dd>
                <dt class="col-sm-3">Tanggal surat</dt>
                <dd class="col-sm-9">{{ $agendaSurat->tanggal_surat?->format('d/m/Y') ?? '—' }}</dd>
                @if ($agendaSurat->jenis === 'masuk')
                    <dt class="col-sm-3">Tanggal terima</dt>
                    <dd class="col-sm-9">{{ $agendaSurat->tanggal_terima?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-3">Dari</dt>
                    <dd class="col-sm-9">{{ $agendaSurat->dari ?: '—' }}</dd>
                @else
                    <dt class="col-sm-3">Kepada</dt>
                    <dd class="col-sm-9">{{ $agendaSurat->kepada ?: '—' }}</dd>
                    <dt class="col-sm-3">Tembusan</dt>
                    <dd class="col-sm-9">{{ $agendaSurat->tembusan ?: '—' }}</dd>
                @endif
                <dt class="col-sm-3">Perihal</dt>
                <dd class="col-sm-9">{{ $agendaSurat->perihal }}</dd>
                <dt class="col-sm-3">Lampiran</dt>
                <dd class="col-sm-9">{{ $agendaSurat->lampiran ?: '—' }}</dd>
                @if ($agendaSurat->catatan)
                    <dt class="col-sm-3">Catatan</dt>
                    <dd class="col-sm-9">{{ $agendaSurat->catatan }}</dd>
                @endif
            </dl>
        </div>
    </div>

    @if ($agendaSurat->jenis === 'masuk')
        <div class="card mb-3">
            <div class="card-header">Disposisi</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Penerima</th>
                            <th>Instruksi</th>
                            <th>Status</th>
                            <th>Tenggat</th>
                            <th>Terlambat</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agendaSurat->disposisi as $disposisi)
                            <tr>
                                <td>
                                    @if ($disposisi->pokja)
                                        Pokja {{ $disposisi->pokja->kode }}
                                    @elseif ($disposisi->user)
                                        {{ $disposisi->user->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $disposisi->instruksi ?: '—' }}</td>
                                <td>{{ ucfirst($disposisi->status) }}</td>
                                <td>{{ $disposisi->tenggat?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    @if ($disposisi->terlambat)
                                        <span class="badge text-bg-danger">Terlambat</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('disposisi.update', $disposisi) }}" class="row g-1 align-items-center">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-auto">
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="baru" @selected($disposisi->status === 'baru')>Baru</option>
                                                <option value="proses" @selected($disposisi->status === 'proses')>Proses</option>
                                                <option value="selesai" @selected($disposisi->status === 'selesai')>Selesai</option>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <input type="date" name="tenggat" class="form-control form-control-sm"
                                                value="{{ $disposisi->tenggat?->format('Y-m-d') }}">
                                        </div>
                                        <div class="col-auto flex-grow-1">
                                            <input type="text" name="instruksi" class="form-control form-control-sm"
                                                placeholder="Instruksi" value="{{ $disposisi->instruksi }}">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Belum ada disposisi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <h6 class="mb-2">Tambah disposisi</h6>
                <form method="post" action="{{ route('agenda-surat.disposisi.store', $agendaSurat) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label" for="pokja_id">Pokja</label>
                        <select name="pokja_id" id="pokja_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach ($pokjaList as $pokja)
                                <option value="{{ $pokja->id }}">Pokja {{ $pokja->kode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="user_id">Pengguna</label>
                        <select name="user_id" id="user_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach ($userList as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="tenggat">Tenggat</label>
                        <input type="date" name="tenggat" id="tenggat" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="instruksi">Instruksi</label>
                        <input type="text" name="instruksi" id="instruksi" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-success btn-sm w-100">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
