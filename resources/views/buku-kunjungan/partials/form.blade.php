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
    <div class="col-md-4">
        <label class="form-label" for="tanggal">Tanggal</label>
        <input type="date" name="tanggal" id="tanggal" class="form-control" required
            value="{{ old('tanggal', optional($bukuKunjungan->tanggal ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="orang_id">Anggota (opsional)</label>
        <select name="orang_id" id="orang_id" class="form-select">
            <option value="">— Manual —</option>
            @foreach ($orangList as $orang)
                <option value="{{ $orang->id }}" @selected((string) old('orang_id', $bukuKunjungan->orang_id ?? '') === (string) $orang->id)>
                    {{ $orang->nama }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="nama">Nama</label>
        <input type="text" name="nama" id="nama" class="form-control" required
            value="{{ old('nama', $bukuKunjungan->nama ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="jabatan">Jabatan</label>
        <input type="text" name="jabatan" id="jabatan" class="form-control"
            value="{{ old('jabatan', $bukuKunjungan->jabatan ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="lokasi_kunjungan">Lokasi kunjungan</label>
        <input type="text" name="lokasi_kunjungan" id="lokasi_kunjungan" class="form-control"
            value="{{ old('lokasi_kunjungan', $bukuKunjungan->lokasi_kunjungan ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="jenis_kegiatan">Jenis kegiatan</label>
        <input type="text" name="jenis_kegiatan" id="jenis_kegiatan" class="form-control"
            value="{{ old('jenis_kegiatan', $bukuKunjungan->jenis_kegiatan ?? '') }}">
    </div>
    <div class="col-md-12">
        <label class="form-label" for="keterangan">Keterangan</label>
        <textarea name="keterangan" id="keterangan" class="form-control" rows="2">{{ old('keterangan', $bukuKunjungan->keterangan ?? '') }}</textarea>
    </div>
</div>
