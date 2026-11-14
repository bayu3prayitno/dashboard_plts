<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Profil pengguna yang sedang login. */
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    /** Simpan perubahan nama, username, dan email (modal "Edit Profil"). */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ], [], ['name' => 'nama', 'username' => 'username', 'email' => 'email']);

        $user->update($data);

        return redirect()->route('profile.show')->with('status', 'Profil berhasil diperbarui.');
    }

    /** Ganti kata sandi setelah memverifikasi kata sandi lama (modal "Ubah Password"). */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('profile.show')->with('status', 'Kata sandi berhasil diubah.');
    }
}
