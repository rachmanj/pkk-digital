@php
    $selectedUnit = $selectedUnit ?? '';
    $inputId = $inputId ?? 'unit';
    $inputName = $inputName ?? 'unit';
    $cssClass = $cssClass ?? 'form-select';
    $semuaLabel = $semuaLabel ?? null;
@endphp
<select name="{{ $inputName }}" id="{{ $inputId }}" class="{{ $cssClass }}">
    @if ($semuaLabel !== null)
        <option value="" @selected($selectedUnit === '' || $selectedUnit === null)>{{ $semuaLabel }}</option>
    @endif
    <optgroup label="Tingkat Kelurahan">
        @if ($semuaLabel === null)
            <option value="" @selected($selectedUnit === '' || $selectedUnit === null)>Umum (tingkat kelurahan)</option>
        @endif
        <option value="ketua" @selected($selectedUnit === 'ketua')>Ketua</option>
        <option value="sekretaris" @selected($selectedUnit === 'sekretaris')>Sekretaris</option>
    </optgroup>
    <optgroup label="Pokja">
        @foreach ($pokjaList as $pokja)
            <option value="pokja-{{ $pokja->id }}" @selected($selectedUnit === 'pokja-'.$pokja->id)>
                Pokja {{ $pokja->kode }}
            </option>
        @endforeach
    </optgroup>
</select>
