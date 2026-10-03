@php
    $pokjaId = old('pokja_id', $bukuTamu->pokja_id ?? null);
    if ($pokjaId === null && isset($buku) && str_starts_with((string) $buku, 'pokja-')) {
        $pokjaId = (int) substr($buku, 6);
    }
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
    <div class="col-md-4">
        <label class="form-label" for="pokja_id">Buku</label>
        <select name="pokja_id" id="pokja_id" class="form-select">
            <option value="">Kelurahan</option>
            @foreach ($pokjaList as $pokja)
                <option value="{{ $pokja->id }}" @selected((string) $pokjaId === (string) $pokja->id)>
                    Pokja {{ $pokja->kode }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="tanggal">Tanggal</label>
        <input type="date" name="tanggal" id="tanggal" class="form-control" required
            value="{{ old('tanggal', optional($bukuTamu->tanggal ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="nama_tamu">Nama tamu</label>
        <input type="text" name="nama_tamu" id="nama_tamu" class="form-control" required
            value="{{ old('nama_tamu', $bukuTamu->nama_tamu ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="alamat">Alamat</label>
        <input type="text" name="alamat" id="alamat" class="form-control"
            value="{{ old('alamat', $bukuTamu->alamat ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="keperluan">Keperluan</label>
        <input type="text" name="keperluan" id="keperluan" class="form-control"
            value="{{ old('keperluan', $bukuTamu->keperluan ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="tujuan">Tujuan</label>
        <input type="text" name="tujuan" id="tujuan" class="form-control"
            value="{{ old('tujuan', $bukuTamu->tujuan ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="keterangan">Keterangan</label>
        <input type="text" name="keterangan" id="keterangan" class="form-control"
            value="{{ old('keterangan', $bukuTamu->keterangan ?? '') }}">
    </div>
</div>
