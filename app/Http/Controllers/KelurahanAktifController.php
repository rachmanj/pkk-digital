<?php

namespace App\Http\Controllers;

use App\Support\ActiveKelurahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KelurahanAktifController extends Controller
{
    public function update(Request $request, ActiveKelurahan $activeKelurahan): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->hasRole('superadmin')) {
            abort(403);
        }

        $kelurahanId = (int) $request->input('kelurahan_id');
        if (! $activeKelurahan->setForSuperadmin($kelurahanId, $user)) {
            return back()->with('error', 'Kelurahan tidak valid.');
        }

        return back()->with('success', 'Kelurahan aktif diperbarui.');
    }
}
