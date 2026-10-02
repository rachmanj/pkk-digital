<?php

namespace App\Http\Controllers;

use App\Models\Kelurahan;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $kelurahan = Kelurahan::query()
            ->where('is_active', true)
            ->with(['pokja' => fn ($query) => $query->orderBy('kode')])
            ->first();

        $pokja = $kelurahan?->pokja ?? collect();
        $pokjaCount = $pokja->count();

        return view('dashboard', [
            'kelurahan' => $kelurahan,
            'pokja' => $pokja,
            'pokjaCount' => $pokjaCount,
        ]);
    }
}
