@php
    use App\Models\StrukturPengurus;
    $record = $struktur ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="unit">Unit</label>
        <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
            @foreach (StrukturPengurus::unitNilai() as $nilai)
                <option value="{{ $nilai }}" @selected(old('unit', $record?->unit ?? ($unit ?? StrukturPengurus::UNIT_TP_PKK)) === $nilai)>
                    {{ StrukturPengurus::labelUnit($nilai) }}
                </option>
            @endforeach
        </select>
        @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="pokja_id">Pokja (jika unit Pokja)</label>
        <select name="pokja_id" id="pokja_id" class="form-select @error('pokja_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($pokjaList as $pokja)
                <option value="{{ $pokja->id }}" @selected((string) old('pokja_id', $record?->pokja_id) === (string) $pokja->id)>
                    Pokja {{ $pokja->kode }} — {{ $pokja->nama }}
                </option>
            @endforeach
        </select>
        @error('pokja_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="rt">RT (untuk LBS / PHBS)</label>
        <input type="text" name="rt" id="rt" class="form-control @error('rt') is-invalid @enderror"
            value="{{ old('rt', $record?->rt) }}" maxlength="20">
        @error('rt')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="jabatan">Jabatan</label>
        <input type="text" name="jabatan" id="jabatan" class="form-control @error('jabatan') is-invalid @enderror"
            value="{{ old('jabatan', $record?->jabatan) }}" required maxlength="120">
        @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="nama">Nama</label>
        <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
            value="{{ old('nama', $record?->nama) }}" required maxlength="200">
        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="urutan">Urutan</label>
        <input type="number" name="urutan" id="urutan" class="form-control @error('urutan') is-invalid @enderror"
            value="{{ old('urutan', $record?->urutan ?? 0) }}" min="0" max="65535">
        @error('urutan')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-9">
        <label class="form-label" for="keterangan">Keterangan</label>
        <input type="text" name="keterangan" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror"
            value="{{ old('keterangan', $record?->keterangan) }}" maxlength="2000">
        @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
