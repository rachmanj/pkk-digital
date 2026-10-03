@php
    $jenisValue = old('jenis', $agendaSurat->jenis ?? $jenis ?? 'masuk');
    $pokjaId = old('pokja_id', $agendaSurat->pokja_id ?? null);
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
    <div class="col-md-3">
        <label class="form-label" for="jenis">Jenis</label>
        <select name="jenis" id="jenis" class="form-select" required>
            <option value="masuk" @selected($jenisValue === 'masuk')>Masuk</option>
            <option value="keluar" @selected($jenisValue === 'keluar')>Keluar</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="pokja_id">Pokja (opsional)</label>
        <select name="pokja_id" id="pokja_id" class="form-select">
            <option value="">Kelurahan</option>
            @foreach ($pokjaList as $pokja)
                <option value="{{ $pokja->id }}" @selected((string) $pokjaId === (string) $pokja->id)>
                    Pokja {{ $pokja->kode }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="tanggal_surat">Tanggal surat</label>
        <input type="date" name="tanggal_surat" id="tanggal_surat" class="form-control" required
            value="{{ old('tanggal_surat', optional($agendaSurat->tanggal_surat ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3" id="field-tanggal-terima">
        <label class="form-label" for="tanggal_terima">Tanggal terima</label>
        <input type="date" name="tanggal_terima" id="tanggal_terima" class="form-control"
            value="{{ old('tanggal_terima', optional($agendaSurat->tanggal_terima ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="no_surat">Nomor surat</label>
        <input type="text" name="no_surat" id="no_surat" class="form-control" required
            value="{{ old('no_surat', $agendaSurat->no_surat ?? '') }}">
    </div>
    <div class="col-md-4" id="field-dari">
        <label class="form-label" for="dari">Dari</label>
        <input type="text" name="dari" id="dari" class="form-control"
            value="{{ old('dari', $agendaSurat->dari ?? '') }}">
    </div>
    <div class="col-md-4" id="field-kepada">
        <label class="form-label" for="kepada">Kepada</label>
        <input type="text" name="kepada" id="kepada" class="form-control"
            value="{{ old('kepada', $agendaSurat->kepada ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="perihal">Perihal</label>
        <input type="text" name="perihal" id="perihal" class="form-control" required
            value="{{ old('perihal', $agendaSurat->perihal ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="lampiran_keterangan">Keterangan lampiran</label>
        <input type="text" name="lampiran_keterangan" id="lampiran_keterangan" class="form-control"
            value="{{ old('lampiran_keterangan', $agendaSurat->lampiran ?? '') }}">
    </div>
    <div class="col-md-4" id="field-tembusan">
        <label class="form-label" for="tembusan">Tembusan</label>
        <input type="text" name="tembusan" id="tembusan" class="form-control"
            value="{{ old('tembusan', $agendaSurat->tembusan ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="lampiran">Berkas scan (PDF/gambar, maks. 5 MB)</label>
        <input type="file" name="lampiran" id="lampiran" class="form-control" accept=".pdf,image/*">
        @if (! empty($agendaSurat?->file_path))
            <div class="form-text">Berkas saat ini tersimpan. Unggah baru untuk mengganti.</div>
        @endif
    </div>
    <div class="col-12">
        <label class="form-label" for="catatan">Catatan</label>
        <textarea name="catatan" id="catatan" class="form-control" rows="2">{{ old('catatan', $agendaSurat->catatan ?? '') }}</textarea>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const jenis = document.getElementById('jenis');
        if (! jenis) {
            return;
        }
        const toggle = () => {
            const v = jenis.value;
            document.getElementById('field-tanggal-terima').style.display = v === 'masuk' ? '' : 'none';
            document.getElementById('field-dari').style.display = v === 'masuk' ? '' : 'none';
            document.getElementById('field-kepada').style.display = v === 'keluar' ? '' : 'none';
            document.getElementById('field-tembusan').style.display = v === 'keluar' ? '' : 'none';
        };
        jenis.addEventListener('change', toggle);
        toggle();
    });
</script>
