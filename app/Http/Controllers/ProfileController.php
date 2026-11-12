<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\Venta;
use App\Models\Compra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        if ($user->rol_id === 3) {
            return view('cliente.perfil', [
                'user' => $user,
            ]);
        }
        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!empty($validated['nombre'])) {
            $user->nombre = trim($validated['nombre']);
        }
        if (!empty($validated['apellido'])) {
            $user->apellido = trim($validated['apellido']);
        }
        // Fallback si se envió name desde formulario original de Breeze
        if (!empty($validated['name']) && empty($validated['nombre'])) {
            $parts = explode(' ', trim($validated['name']), 2);
            $user->nombre = $parts[0];
            $user->apellido = $parts[1] ?? ($user->apellido ?? '');
        }

        if (array_key_exists('telefono', $validated)) {
            $user->telefono = trim($validated['telefono'] ?? '');
        }

        $user->email = trim($validated['email']);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // 1. Prevenir que el único administrador activo elimine su cuenta
        if ((int)$user->rol_id === 1 && User::where('rol_id', 1)->where('estado', 'Activo')->count() <= 1) {
            return Redirect::route('profile.edit')->withErrors([
                'userDeletion' => 'No puedes eliminar tu cuenta porque eres el único administrador activo del sistema.'
            ], 'userDeletion');
        }

        // 2. Comprobar si tiene transacciones históricas para no violar la integridad referencial (restrictOnDelete)
        $tieneTransacciones = $user->ventas()->exists()
            || Venta::where('usuario_id', $user->id)->exists()
            || Compra::where('usuario_id', $user->id)->exists();

        if ($tieneTransacciones) {
            // Se desactiva y anonimiza la cuenta para preservar la trazabilidad contable y legal
            $user->update([
                'estado'   => 'Inactivo',
                'email'    => 'deleted_' . $user->id . '_' . time() . '@itsfashion.local',
                'username' => 'deleted_' . $user->id . '_' . time(),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/')->with('status', 'Tu cuenta ha sido cerrada y tus accesos desactivados.');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
