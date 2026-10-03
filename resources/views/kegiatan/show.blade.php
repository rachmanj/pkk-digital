@extends('layouts.app', [
    'title' => 'Detail Kegiatan — Buku PKK Digital',
    'header' => 'Detail Kegiatan',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('kegiatan.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke daftar</a>
        <a href="{{ route('kegiatan.edit', $kegiatan) }}" class="btn btn-primary btn-sm">Ubah</a>
        <form method="post" action="{{ route('kegiatan.destroy', $kegiatan) }}"
            onsubmit="return confirm('Hapus kegiatan ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
        </form>
    </div>

    <div class="card mb-3">
        <div class="card-header">Rincian Kegiatan</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nama</dt>
                <dd class="col-sm-9">{{ $kegiatan->nama }}</dd>
                <dt class="col-sm-3">Jenis</dt>
                <dd class="col-sm-9">{{ $kegiatan->labelJenis() }}</dd>
                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ $kegiatan->tanggal?->format('d/m/Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Waktu</dt>
                <dd class="col-sm-9">
                    @if ($kegiatan->jam_mulai || $kegiatan->jam_selesai)
                        {{ $kegiatan->jam_mulai ? substr((string) $kegiatan->jam_mulai, 0, 5) : '—' }}
                        –
                        {{ $kegiatan->jam_selesai ? substr((string) $kegiatan->jam_selesai, 0, 5) : '—' }}
                    @else
                        —
                    @endif
                </dd>
                <dt class="col-sm-3">Tempat</dt>
                <dd class="col-sm-9">{{ $kegiatan->tempat ?: '—' }}</dd>
                <dt class="col-sm-3">Acara</dt>
                <dd class="col-sm-9">{{ $kegiatan->acara ?: '—' }}</dd>
                <dt class="col-sm-3">Pokja</dt>
                <dd class="col-sm-9">
                    @if ($kegiatan->pokja)
                        Pokja {{ $kegiatan->pokja->kode }}
                    @else
                        —
                    @endif
                </dd>
                <dt class="col-sm-3">RT</dt>
                <dd class="col-sm-9">
                    @if ($kegiatan->rt)
                        RT {{ $kegiatan->rt->nomor }}
                    @else
                        —
                    @endif
                </dd>
                <dt class="col-sm-3">Pimpinan rapat</dt>
                <dd class="col-sm-9">{{ $kegiatan->pimpinanRapat?->nama ?? '—' }}</dd>
                <dt class="col-sm-3">Uraian</dt>
                <dd class="col-sm-9">{{ $kegiatan->uraian ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Presensi (Daftar Hadir)</span>
            <span class="badge text-bg-primary">Hadir: {{ $kegiatan->hadirCount() }} / {{ $kegiatan->presensi->count() }}</span>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('kegiatan.presensi.store', $kegiatan) }}" id="form-presensi">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="cari-orang">Tambah dari data anggota</label>
                        <input type="search" id="cari-orang" class="form-control form-control-sm"
                            placeholder="Ketik nama untuk mencari…" autocomplete="off">
                        <div id="hasil-cari-orang" class="list-group mt-1" style="max-height: 160px; overflow-y: auto;"></div>
                    </div>
                    <div class="col-md-7 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-baris-manual">
                            <i class="bi bi-person-plus"></i> Baris manual
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tabel-presensi">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 3rem">No</th>
                                <th>Nama</th>
                                <th>Alamat / Jabatan</th>
                                <th style="width: 5rem">Hadir</th>
                                <th>Keterangan</th>
                                <th style="width: 3rem"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kegiatan->presensi as $presensi)
                                <tr class="baris-peserta">
                                    <td class="nomor-urut">{{ $presensi->urut }}</td>
                                    <td>
                                        @if ($presensi->orang_id)
                                            <input type="hidden" name="peserta[{{ $loop->index }}][orang_id]" value="{{ $presensi->orang_id }}">
                                            {{ $presensi->nama_tampil }}
                                        @else
                                            <input type="text" name="peserta[{{ $loop->index }}][nama_manual]" class="form-control form-control-sm"
                                                value="{{ $presensi->nama_manual }}" required>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($presensi->orang_id)
                                            {{ $presensi->orang?->alamat ?: '—' }}
                                        @else
                                            <input type="text" name="peserta[{{ $loop->index }}][alamat_manual]" class="form-control form-control-sm mb-1"
                                                placeholder="Alamat" value="{{ $presensi->alamat_manual }}">
                                            <input type="text" name="peserta[{{ $loop->index }}][jabatan_manual]" class="form-control form-control-sm"
                                                placeholder="Jabatan" value="{{ $presensi->jabatan_manual }}">
                                        @endif
                                    </td>
                                    <td>
                                        <input type="hidden" name="peserta[{{ $loop->index }}][hadir]" value="0">
                                        <input type="checkbox" class="form-check-input chk-hadir" name="peserta[{{ $loop->index }}][hadir]" value="1"
                                            @checked($presensi->hadir)>
                                    </td>
                                    <td>
                                        <input type="text" name="peserta[{{ $loop->index }}][keterangan]" class="form-control form-control-sm"
                                            value="{{ $presensi->keterangan }}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris" title="Hapus baris">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Simpan daftar presensi</button>
            </form>

            @if ($kegiatan->presensi->isNotEmpty())
                <hr class="my-3">
                <p class="small text-muted mb-2">Ubah kehadiran satu peserta tanpa mengubah nomor urut:</p>
                <div class="d-flex flex-column gap-2">
                    @foreach ($kegiatan->presensi as $presensi)
                        <form method="post" action="{{ route('kegiatan.presensi.update', [$kegiatan, $presensi]) }}"
                            class="d-flex flex-wrap gap-2 align-items-center border rounded p-2">
                            @csrf
                            @method('PATCH')
                            <span class="fw-semibold">{{ $presensi->urut }}.</span>
                            <span>{{ $presensi->nama_tampil }}</span>
                            <input type="hidden" name="hadir" value="0">
                            <label class="form-check-label ms-2">
                                <input type="checkbox" class="form-check-input" name="hadir" value="1" @checked($presensi->hadir)>
                                Hadir
                            </label>
                            <input type="text" name="keterangan" class="form-control form-control-sm" style="max-width: 14rem"
                                placeholder="Keterangan" value="{{ $presensi->keterangan }}">
                            <button type="submit" class="btn btn-outline-primary btn-sm">Perbarui</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Notulen Rapat</div>
        <div class="card-body">
            <form method="post" action="{{ route('kegiatan.notulen.store', $kegiatan) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="macam_rapat">Macam rapat</label>
                        <input type="text" name="macam_rapat" id="macam_rapat" class="form-control"
                            value="{{ old('macam_rapat', $notulen->macam_rapat) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="jumlah_diundang">Jumlah diundang</label>
                        <input type="number" name="jumlah_diundang" id="jumlah_diundang" class="form-control" min="0"
                            value="{{ old('jumlah_diundang', $notulen->jumlah_diundang) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="jumlah_hadir">Jumlah hadir</label>
                        <input type="number" name="jumlah_hadir" id="jumlah_hadir" class="form-control" min="0"
                            value="{{ old('jumlah_hadir', $notulen->jumlah_hadir) }}"
                            placeholder="Otomatis dari presensi">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="jumlah_tidak_hadir">Jumlah tidak hadir</label>
                        <input type="number" name="jumlah_tidak_hadir" id="jumlah_tidak_hadir" class="form-control" min="0"
                            value="{{ old('jumlah_tidak_hadir', $notulen->jumlah_tidak_hadir) }}"
                            placeholder="Otomatis dari presensi">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="uraian_jalannya">Uraian jalannya rapat</label>
                        <textarea name="uraian_jalannya" id="uraian_jalannya" class="form-control" rows="4">{{ old('uraian_jalannya', $notulen->uraian_jalannya) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="keputusan">Keputusan</label>
                        <textarea name="keputusan" id="keputusan" class="form-control" rows="3">{{ old('keputusan', $notulen->keputusan) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="lain_lain">Lain-lain</label>
                        <textarea name="lain_lain" id="lain_lain" class="form-control" rows="2">{{ old('lain_lain', $notulen->lain_lain) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="penutup">Penutup</label>
                        <textarea name="penutup" id="penutup" class="form-control" rows="2">{{ old('penutup', $notulen->penutup) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tempat_tanggal_ttd">Tempat dan tanggal tanda tangan</label>
                        <input type="text" name="tempat_tanggal_ttd" id="tempat_tanggal_ttd" class="form-control"
                            value="{{ old('tempat_tanggal_ttd', $notulen->tempat_tanggal_ttd) }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">Simpan notulen</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            const tbody = document.querySelector('#tabel-presensi tbody');
            const cariInput = document.getElementById('cari-orang');
            const hasilCari = document.getElementById('hasil-cari-orang');
            const cariUrl = @json(route('kegiatan.cari-orang'));
            let rowIndex = tbody ? tbody.querySelectorAll('tr').length : 0;

            function renumberRows() {
                if (!tbody) return;
                tbody.querySelectorAll('tr.baris-peserta').forEach((tr, idx) => {
                    tr.querySelectorAll('[name^="peserta["]').forEach((el) => {
                        el.name = el.name.replace(/peserta\[\d+\]/, 'peserta[' + idx + ']');
                    });
                    const nomor = tr.querySelector('.nomor-urut');
                    if (nomor) nomor.textContent = String(idx + 1);
                });
                rowIndex = tbody.querySelectorAll('tr').length;
            }

            function addOrangRow(orang) {
                const tr = document.createElement('tr');
                tr.className = 'baris-peserta';
                tr.innerHTML = `
                    <td class="nomor-urut"></td>
                    <td>
                        <input type="hidden" name="peserta[${rowIndex}][orang_id]" value="${orang.id}">
                        ${orang.nama}
                    </td>
                    <td>${orang.alamat || '—'}</td>
                    <td>
                        <input type="hidden" name="peserta[${rowIndex}][hadir]" value="0">
                        <input type="checkbox" class="form-check-input chk-hadir" name="peserta[${rowIndex}][hadir]" value="1" checked>
                    </td>
                    <td><input type="text" name="peserta[${rowIndex}][keterangan]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris">&times;</button></td>
                `;
                tbody.appendChild(tr);
                renumberRows();
            }

            function addManualRow() {
                const tr = document.createElement('tr');
                tr.className = 'baris-peserta';
                tr.innerHTML = `
                    <td class="nomor-urut"></td>
                    <td><input type="text" name="peserta[${rowIndex}][nama_manual]" class="form-control form-control-sm" required></td>
                    <td>
                        <input type="text" name="peserta[${rowIndex}][alamat_manual]" class="form-control form-control-sm mb-1" placeholder="Alamat">
                        <input type="text" name="peserta[${rowIndex}][jabatan_manual]" class="form-control form-control-sm" placeholder="Jabatan">
                    </td>
                    <td>
                        <input type="hidden" name="peserta[${rowIndex}][hadir]" value="0">
                        <input type="checkbox" class="form-check-input chk-hadir" name="peserta[${rowIndex}][hadir]" value="1" checked>
                    </td>
                    <td><input type="text" name="peserta[${rowIndex}][keterangan]" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-hapus-baris">&times;</button></td>
                `;
                tbody.appendChild(tr);
                renumberRows();
            }

            document.getElementById('btn-baris-manual')?.addEventListener('click', addManualRow);

            tbody?.addEventListener('click', (e) => {
                if (e.target.closest('.btn-hapus-baris')) {
                    e.target.closest('tr')?.remove();
                    renumberRows();
                }
            });

            let debounce;
            cariInput?.addEventListener('input', () => {
                clearTimeout(debounce);
                const q = cariInput.value.trim();
                if (q.length < 2) {
                    hasilCari.innerHTML = '';
                    return;
                }
                debounce = setTimeout(() => {
                    fetch(cariUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then((r) => r.json())
                        .then((items) => {
                            hasilCari.innerHTML = '';
                            items.forEach((orang) => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'list-group-item list-group-item-action list-group-item-sm py-1';
                                btn.textContent = orang.nama + (orang.alamat ? ' — ' + orang.alamat : '');
                                btn.addEventListener('click', () => {
                                    addOrangRow(orang);
                                    hasilCari.innerHTML = '';
                                    cariInput.value = '';
                                });
                                hasilCari.appendChild(btn);
                            });
                        });
                }, 300);
            });

            renumberRows();
        })();
    </script>
@endsection
