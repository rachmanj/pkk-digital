<?php

namespace App\Http\Requests\Concerns;

use App\Models\KasTransaksi;
use App\Models\Pokja;
use Illuminate\Validation\Rule;

trait ValidatesKasTransaksiInput
{
    /**
     * @return array<string, mixed>
     */
    protected function kasTransaksiFieldRules(): array
    {
        $batasTanggal = now()->addDay()->toDateString();

        return [
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'jenis' => ['required', 'string', Rule::in([KasTransaksi::JENIS_MASUK, KasTransaksi::JENIS_KELUAR])],
            'pos' => ['required', 'string', Rule::in([KasTransaksi::POS_TUNAI, KasTransaksi::POS_BANK])],
            'tanggal' => ['required', 'date', 'before_or_equal:'.$batasTanggal],
            'sumber_dana' => ['nullable', 'string', 'max:255'],
            'uraian' => ['required', 'string', 'max:2000'],
            'no_bukti' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function kasTransaksiFieldMessages(): array
    {
        return [
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'jenis.required' => 'Jenis transaksi wajib dipilih.',
            'jenis.in' => 'Jenis transaksi harus pemasukan atau pengeluaran.',
            'pos.required' => 'Tempat uang wajib dipilih.',
            'pos.in' => 'Tempat uang harus tunai atau bank.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari satu hari di masa depan.',
            'uraian.required' => 'Uraian wajib diisi.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.gt' => 'Jumlah harus lebih dari nol.',
        ];
    }
}
