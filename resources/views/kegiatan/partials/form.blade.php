@php
    $jenisValue = old('jenis', $kegiatan->jenis ?? \App\Models\Kegiatan::JENIS_RAPAT);
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
    <div class="col-md-6">
        <label class="form-label" for="nama">Nama kegiatan</label>
        <input type="text" name="nama" id="nama" class="form-control" required
            value="{{ old('nama', $kegiatan->nama ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="jenis">Jenis</label>
        <select name="jenis" id="jenis" class="form-select" required>
            @foreach ($jenisList as $jenisItem)
                <option value="{{ $jenisItem }}" @selected($jenisValue === $jenisItem)>
                    {{ (new \App\Models\Kegiatan(['jenis' => $jenisItem]))->labelJenis() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="tanggal">Tanggal</label>
        <input type="date" name="tanggal" id="tanggal" class="form-control" required
            value="{{ old('tanggal', optional($kegiatan->tanggal ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="jam_mulai">Jam mulai</label>
        <input type="time" name="jam_mulai" id="jam_mulai" class="form-control"
            value="{{ old('jam_mulai', ($kegiatan?->jam_mulai) ? substr((string) $kegiatan->jam_mulai, 0, 5) : '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="jam_selesai">Jam selesai</label>
        <input type="time" name="jam_selesai" id="jam_selesai" class="form-control"
            value="{{ old('jam_selesai', ($kegiatan?->jam_selesai) ? substr((string) $kegiatan->jam_selesai, 0, 5) : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="unit">Unit</label>
        @include('kegiatan.partials.unit-select', [
            'pokjaList' => $pokjaList,
            'selectedUnit' => old('unit', $kegiatan instanceof \App\Models\Kegiatan
                ? \App\Support\KegiatanUnit::nilaiFormDariKegiatan($kegiatan)
                : ''),
            'inputId' => 'unit',
            'inputName' => 'unit',
        ])
    </div>
    <div class="col-md-4">
        <label class="form-label" for="rt_id">RT (opsional)</label>
        <select name="rt_id" id="rt_id" class="form-select">
            <option value="">—</option>
            @foreach ($rtList as $rt)
                <option value="{{ $rt->id }}" @selected((string) old('rt_id', $kegiatan->rt_id ?? '') === (string) $rt->id)>
                    RT {{ $rt->nomor }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="pimpinan_rapat_id">Pimpinan rapat</label>
        <select name="pimpinan_rapat_id" id="pimpinan_rapat_id" class="form-select">
            <option value="">—</option>
            @foreach ($orangList as $orang)
                <option value="{{ $orang->id }}" @selected((string) old('pimpinan_rapat_id', $kegiatan->pimpinan_rapat_id ?? '') === (string) $orang->id)>
                    {{ $orang->nama }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="tempat">Tempat</label>
        <input type="text" name="tempat" id="tempat" class="form-control" required
            value="{{ old('tempat', $kegiatan->tempat ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="acara">Acara</label>
        <input type="text" name="acara" id="acara" class="form-control" required
            value="{{ old('acara', $kegiatan->acara ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="uraian">Uraian</label>
        <textarea name="uraian" id="uraian" class="form-control" rows="3">{{ old('uraian', $kegiatan->uraian ?? '') }}</textarea>
    </div>
    @isset($programKerjaList)
        <div class="col-12">
            <label class="form-label" for="program_kerja_id">Tautkan ke program kerja (opsional)</label>
            <select name="program_kerja_id" id="program_kerja_id" class="form-select">
                <option value="">— Tidak ditaut —</option>
                @foreach ($programKerjaList as $programKerja)
                    <option value="{{ $programKerja->id }}" @selected((string) old('program_kerja_id', $kegiatan->program_kerja_id ?? '') === (string) $programKerja->id)>
                        @if ($programKerja->kode)
                            [{{ $programKerja->kode }}]
                        @endif
                        {{ $programKerja->kegiatan }} ({{ $programKerja->tahun }})
                    </option>
                @endforeach
            </select>
            <div class="form-text">Hanya butir program kerja dengan tahun dan unit yang sama dengan kegiatan.</div>
        </div>
    @endisset
</div>
