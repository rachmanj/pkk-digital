<?php

namespace App\Http\Requests\Concerns;

use App\Models\InventarisBarang;
use App\Models\Pokja;
use Illuminate\Validation\Rule;

trait ValidatesInventarisBarangInput
{
    /**
     * @return array<string, mixed>
     */
    protected function inventarisBarangFieldRules(): array
    {
        $batasTanggal = now()->addDay()->toDateString();

        return [
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'nama_barang' => ['required', 'string', 'max:255'],
            'asal_barang' => ['nullable', 'string', 'max:255'],
            'tanggal_terima' => ['required', 'date', 'before_or_equal:'.$batasTanggal],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tempat_penyimpanan' => ['nullable', 'string', 'max:255'],
            'kondisi' => ['required', 'string', Rule::in(InventarisBarang::kondisiNilai())],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function inventarisBarangFieldMessages(): array
    {
        return [
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'tanggal_terima.required' => 'Tanggal penerimaan/pembelian wajib diisi.',
            'tanggal_terima.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tanggal_terima.before_or_equal' => 'Tanggal tidak boleh lebih dari satu hari di masa depan.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.integer' => 'Jumlah harus berupa angka bulat.',
            'jumlah.min' => 'Jumlah harus lebih dari nol.',
            'kondisi.required' => 'Kondisi barang wajib dipilih.',
            'kondisi.in' => 'Kondisi barang tidak valid.',
        ];
    }
}
