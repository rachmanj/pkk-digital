@php
    $indexRaw = $index ?? 0;
    if ($indexRaw === '__INDEX__') {
        $rowIndexKey = '__INDEX__';
        $rowLabelNumber = 1;
    } else {
        $rowIndexKey = (int) $indexRaw;
        $rowLabelNumber = $rowIndexKey + 1;
    }
    $data = $item ?? [];
    $rowId = $data['id'] ?? null;
@endphp
<div class="card card-outline card-secondary mb-3 keanggotaan-row" data-index="{{ $rowIndexKey }}">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Keanggotaan #{{ $rowLabelNumber }}</span>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-keanggotaan" @if(($minRows ?? 1) >= ($totalRows ?? 1)) disabled @endif>
            Hapus
        </button>
    </div>
    <div class="card-body">
        @if ($rowId)
            <input type="hidden" name="keanggotaan[{{ $rowIndexKey }}][id]" value="{{ $rowId }}">
        @endif
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                <select name="keanggotaan[{{ $rowIndexKey }}][jenis]" class="form-select form-select-sm" required>
                    <option value="">— Pilih —</option>
                    @foreach (\App\Models\Keanggotaan::JENIS_VALUES as $jenis)
                        <option value="{{ $jenis }}" @selected(($data['jenis'] ?? '') === $jenis)>
                            @switch($jenis)
                                @case(\App\Models\Keanggotaan::JENIS_TP_PKK) Dalam Keanggotaan TP PKK @break
                                @case(\App\Models\Keanggotaan::JENIS_KADER_UMUM) Kader Umum @break
                                @case(\App\Models\Keanggotaan::JENIS_KADER_KHUSUS) Kader Khusus @break
                            @endswitch
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Pokja</label>
                <select name="keanggotaan[{{ $rowIndexKey }}][pokja_id]" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach ($pokjaList as $pokja)
                        <option value="{{ $pokja->id }}" @selected((string) ($data['pokja_id'] ?? '') === (string) $pokja->id)>
                            {{ $pokja->nama }} ({{ $pokja->kode }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Jabatan</label>
                <input type="text" name="keanggotaan[{{ $rowIndexKey }}][jabatan]" class="form-control form-control-sm"
                    value="{{ $data['jabatan'] ?? '' }}" placeholder="Mis. Ketua Pokja I">
            </div>
            <div class="col-md-4">
                <label class="form-label">No. Registrasi TP PKK</label>
                <input type="text" name="keanggotaan[{{ $rowIndexKey }}][no_registrasi]" class="form-control form-control-sm"
                    value="{{ $data['no_registrasi'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">SK / Nomor</label>
                <input type="text" name="keanggotaan[{{ $rowIndexKey }}][sk_nomor]" class="form-control form-control-sm"
                    value="{{ $data['sk_nomor'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Mulai</label>
                <input type="date" name="keanggotaan[{{ $rowIndexKey }}][mulai]" class="form-control form-control-sm"
                    value="{{ isset($data['mulai']) ? (\Illuminate\Support\Carbon::parse($data['mulai'])->format('Y-m-d')) : '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Selesai</label>
                <input type="date" name="keanggotaan[{{ $rowIndexKey }}][selesai]" class="form-control form-control-sm"
                    value="{{ isset($data['selesai']) ? (\Illuminate\Support\Carbon::parse($data['selesai'])->format('Y-m-d')) : '' }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="keanggotaan[{{ $rowIndexKey }}][is_aktif]" value="0">
                    <input class="form-check-input" type="checkbox" name="keanggotaan[{{ $rowIndexKey }}][is_aktif]" value="1"
                        id="keanggotaan_aktif_{{ $rowIndexKey }}" @checked(filter_var($data['is_aktif'] ?? true, FILTER_VALIDATE_BOOLEAN))>
                    <label class="form-check-label" for="keanggotaan_aktif_{{ $rowIndexKey }}">Aktif</label>
                </div>
            </div>
        </div>
    </div>
</div>
