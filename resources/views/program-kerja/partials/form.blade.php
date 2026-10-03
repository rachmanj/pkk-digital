@php
    use App\Models\ProgramKerja;

    $pokjaId = old('pokja_id', $programKerja->pokja_id ?? null);
    if ($pokjaId === null && isset($buku) && str_starts_with((string) $buku, 'pokja-')) {
        $pokjaId = (int) substr($buku, 6);
    }

    $tahunValue = old('tahun', $programKerja->tahun ?? ($tahun ?? now()->year));
    $bulanRencana = ProgramKerja::normalisasiBulan(old('bulan_rencana', $programKerja->bulan_rencana ?? null));
    $bulanPelaksanaan = ProgramKerja::normalisasiBulan(old('bulan_pelaksanaan', $programKerja->bulan_pelaksanaan ?? null));
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="pokja_id">Unit</label>
        <select name="pokja_id" id="pokja_id" class="form-select">
            <option value="">Kelurahan (Sekretariat)</option>
            @foreach ($pokjaList as $pokja)
                <option value="{{ $pokja->id }}" @selected((string) $pokjaId === (string) $pokja->id)>
                    Pokja {{ $pokja->kode }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label" for="tahun">Tahun</label>
        <input type="number" name="tahun" id="tahun" class="form-control" required min="2000" max="2100"
            value="{{ $tahunValue }}">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="kode">No. / Kode baris</label>
        <input type="text" name="kode" id="kode" class="form-control" maxlength="16"
            value="{{ old('kode', $programKerja->kode ?? '') }}" placeholder="A, B, 1, …">
    </div>
    <div class="col-md-5">
        <label class="form-label" for="program">Program</label>
        <input type="text" name="program" id="program" class="form-control"
            value="{{ old('program', $programKerja->program ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="kegiatan">Kegiatan / jenis kegiatan</label>
        <input type="text" name="kegiatan" id="kegiatan" class="form-control" required
            value="{{ old('kegiatan', $programKerja->kegiatan ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="tanggal_kegiatan">Tanggal kegiatan</label>
        <input type="date" name="tanggal_kegiatan" id="tanggal_kegiatan" class="form-control"
            value="{{ old('tanggal_kegiatan', optional($programKerja->tanggal_kegiatan ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="tempat">Tempat</label>
        <input type="text" name="tempat" id="tempat" class="form-control"
            value="{{ old('tempat', $programKerja->tempat ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="sumber_dana">Sumber dana</label>
        <input type="text" name="sumber_dana" id="sumber_dana" class="form-control"
            value="{{ old('sumber_dana', $programKerja->sumber_dana ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="tujuan">Tujuan</label>
        <textarea name="tujuan" id="tujuan" class="form-control" rows="2">{{ old('tujuan', $programKerja->tujuan ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="sasaran">Sasaran</label>
        <textarea name="sasaran" id="sasaran" class="form-control" rows="2">{{ old('sasaran', $programKerja->sasaran ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label" for="keterangan">Keterangan</label>
        <textarea name="keterangan" id="keterangan" class="form-control" rows="2">{{ old('keterangan', $programKerja->keterangan ?? '') }}</textarea>
    </div>
</div>

<div class="mt-4">
    <h6 class="mb-2">Bulan perencanaan</h6>
    <div class="d-flex flex-wrap gap-2">
        @foreach (ProgramKerja::daftarBulan() as $bulan)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="bulan_rencana[]" value="{{ $bulan }}"
                    id="bulan_rencana_{{ $bulan }}" @checked(in_array($bulan, $bulanRencana, true))>
                <label class="form-check-label" for="bulan_rencana_{{ $bulan }}">{{ $bulan }}</label>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-3">
    <h6 class="mb-2">Bulan pelaksanaan (manual)</h6>
    <div class="d-flex flex-wrap gap-2">
        @foreach (ProgramKerja::daftarBulan() as $bulan)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="bulan_pelaksanaan[]" value="{{ $bulan }}"
                    id="bulan_pelaksanaan_{{ $bulan }}" @checked(in_array($bulan, $bulanPelaksanaan, true))>
                <label class="form-check-label" for="bulan_pelaksanaan_{{ $bulan }}">{{ $bulan }}</label>
            </div>
        @endforeach
    </div>
    <p class="small text-muted mb-0 mt-1">Bulan dari kegiatan nyata yang ditaut otomatis ditampilkan di halaman rincian dan matriks cetak.</p>
</div>
