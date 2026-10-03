@php
    $orangModel = $orang ?? null;
    $isEdit = $orangModel !== null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
            value="{{ old('nama', $orangModel?->nama) }}" required>
        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
        <select name="jenis_kelamin" id="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror" required>
            <option value="">—</option>
            <option value="L" @selected(old('jenis_kelamin', $orangModel?->jenis_kelamin) === 'L')>L</option>
            <option value="P" @selected(old('jenis_kelamin', $orangModel?->jenis_kelamin) === 'P')>P</option>
        </select>
        @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="foto" class="form-label">Foto (opsional, maks. 2 MB)</label>
        <input type="file" name="foto" id="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/*">
        @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
        <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control"
            value="{{ old('tempat_lahir', $orangModel?->tempat_lahir) }}">
    </div>
    <div class="col-md-4">
        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir" id="tanggal_lahir"
            class="form-control @error('tanggal_lahir') is-invalid @enderror"
            value="{{ old('tanggal_lahir', $orangModel?->tanggal_lahir?->format('Y-m-d')) }}">
        @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="status_perkawinan" class="form-label">Status</label>
        <input type="text" name="status_perkawinan" id="status_perkawinan" class="form-control"
            value="{{ old('status_perkawinan', $orangModel?->status_perkawinan) }}" placeholder="Mis. Menikah">
    </div>
    <div class="col-md-8">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea name="alamat" id="alamat" class="form-control" rows="2">{{ old('alamat', $orangModel?->alamat) }}</textarea>
    </div>
    <div class="col-md-4">
        <label for="rt_id" class="form-label">RT</label>
        <select name="rt_id" id="rt_id" class="form-select @error('rt_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($rtList as $rt)
                <option value="{{ $rt->id }}" @selected((string) old('rt_id', $orangModel?->rt_id) === (string) $rt->id)>
                    RT {{ $rt->nomor }}@if($rt->dasawisma) — {{ $rt->dasawisma }}@endif
                </option>
            @endforeach
        </select>
        @error('rt_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="pendidikan" class="form-label">Pendidikan</label>
        <input type="text" name="pendidikan" id="pendidikan" class="form-control"
            value="{{ old('pendidikan', $orangModel?->pendidikan) }}">
    </div>
    <div class="col-md-4">
        <label for="pekerjaan" class="form-label">Pekerjaan</label>
        <input type="text" name="pekerjaan" id="pekerjaan" class="form-control"
            value="{{ old('pekerjaan', $orangModel?->pekerjaan) }}">
    </div>
    <div class="col-md-4">
        <label for="no_hp" class="form-label">No. HP</label>
        <input type="text" name="no_hp" id="no_hp" class="form-control"
            value="{{ old('no_hp', $orangModel?->no_hp) }}">
    </div>
    <div class="col-12">
        <label for="catatan" class="form-label">Keterangan</label>
        <textarea name="catatan" id="catatan" class="form-control" rows="2">{{ old('catatan', $orangModel?->catatan) }}</textarea>
    </div>
</div>

<hr class="my-4">

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Keanggotaan</h5>
    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-keanggotaan">
        <i class="bi bi-plus-lg"></i> Tambah Keanggotaan
    </button>
</div>

@php
    $keanggotaanItems = old('keanggotaan');
    if ($keanggotaanItems === null && $orangModel) {
        $keanggotaanItems = $orangModel->keanggotaan->map(fn ($k) => $k->only([
            'id', 'jenis', 'pokja_id', 'jabatan', 'no_registrasi', 'sk_nomor', 'mulai', 'selesai', 'is_aktif',
        ]))->all();
    }
    if (empty($keanggotaanItems)) {
        $keanggotaanItems = [['jenis' => '', 'is_aktif' => true]];
    }
@endphp

<div id="keanggotaan-container">
    @foreach ($keanggotaanItems as $idx => $item)
        @include('orang.partials.keanggotaan-row', [
            'index' => $idx,
            'item' => $item,
            'pokjaList' => $pokjaList,
            'totalRows' => count($keanggotaanItems),
            'minRows' => 1,
        ])
    @endforeach
</div>

<template id="keanggotaan-row-template">
    @include('orang.partials.keanggotaan-row', [
        'index' => '__INDEX__',
        'item' => [],
        'pokjaList' => $pokjaList,
        'totalRows' => 2,
        'minRows' => 1,
    ])
</template>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('keanggotaan-container');
    const template = document.getElementById('keanggotaan-row-template');
    const addBtn = document.getElementById('btn-add-keanggotaan');

    function reindexRows() {
        const rows = container.querySelectorAll('.keanggotaan-row');
        rows.forEach((row, i) => {
            row.dataset.index = i;
            row.querySelector('.fw-semibold').textContent = 'Keanggotaan #' + (i + 1);
            row.querySelectorAll('[name^="keanggotaan["]').forEach(el => {
                el.name = el.name.replace(/keanggotaan\[\d+\]/, 'keanggotaan[' + i + ']');
            });
            const aktif = row.querySelector('[type="checkbox"][name$="[is_aktif]"]');
            if (aktif) {
                aktif.id = 'keanggotaan_aktif_' + i;
                const label = row.querySelector('label[for^="keanggotaan_aktif_"]');
                if (label) label.setAttribute('for', aktif.id);
            }
        });
        const canRemove = rows.length > 1;
        rows.forEach(row => {
            const btn = row.querySelector('.btn-remove-keanggotaan');
            if (btn) btn.disabled = !canRemove;
        });
    }

    addBtn?.addEventListener('click', function () {
        const index = container.querySelectorAll('.keanggotaan-row').length;
        const html = template.innerHTML.replace(/__INDEX__/g, String(index));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        container.appendChild(wrap.firstElementChild);
        reindexRows();
    });

    container?.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-keanggotaan')) {
            const rows = container.querySelectorAll('.keanggotaan-row');
            if (rows.length <= 1) return;
            e.target.closest('.keanggotaan-row')?.remove();
            reindexRows();
        }
    });
});
</script>
@endpush
