<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Http\Requests\StoreDisposisiRequest;
use App\Http\Requests\UpdateDisposisiRequest;
use App\Models\AgendaSurat;
use App\Models\Disposisi;
use Illuminate\Http\RedirectResponse;

class DisposisiController extends Controller
{
    use HandlesPokjaScope;

    public function store(StoreDisposisiRequest $request, AgendaSurat $agendaSurat): RedirectResponse
    {
        $this->authorizePokjaRecord($agendaSurat->pokja_id);

        $validated = $request->validated();

        $agendaSurat->disposisi()->create([
            'pokja_id' => $validated['pokja_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'instruksi' => $validated['instruksi'] ?? null,
            'status' => Disposisi::STATUS_BARU,
            'tenggat' => $validated['tenggat'] ?? null,
        ]);

        return redirect()->route('agenda-surat.show', $agendaSurat)
            ->with('success', 'Disposisi berhasil ditambahkan.');
    }

    public function update(UpdateDisposisiRequest $request, Disposisi $disposisi): RedirectResponse
    {
        $disposisi->loadMissing('agendaSurat');
        $this->authorizePokjaRecord($disposisi->agendaSurat?->pokja_id);

        $validated = $request->validated();
        $payload = [
            'status' => $validated['status'],
            'instruksi' => $validated['instruksi'] ?? null,
            'tenggat' => $validated['tenggat'] ?? null,
        ];

        if ($validated['status'] === Disposisi::STATUS_SELESAI) {
            $payload['selesai_at'] = now();
            $payload['oleh_user_id'] = $request->user()?->id;
        }

        $disposisi->update($payload);

        return redirect()->route('agenda-surat.show', $disposisi->agenda_surat_id)
            ->with('success', 'Disposisi berhasil diperbarui.');
    }
}
