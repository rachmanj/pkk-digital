<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUbahSandiRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UbahSandiController extends Controller
{
    public function show(): View
    {
        return view('ubah-sandi');
    }

    public function update(UpdateUbahSandiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->password = $request->string('password')->toString();
        $user->save();

        activity('pengguna')
            ->causedBy($user)
            ->performedOn($user)
            ->log('password_changed');

        return redirect()->route('ubah-sandi.show')
            ->with('success', 'Kata sandi berhasil diubah.');
    }
}
