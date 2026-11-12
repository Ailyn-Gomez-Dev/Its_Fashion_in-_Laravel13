<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    //la funcion create es para mostrar la vista de registro
    public function create(): View
    { //esta funcion es para mostrar la vista de registro
        return view('auth.register'); //se retorna la vista de registro
    }

    // Handle an incoming registration request. //la funcion store es para guardar los datos del nuevo usuario
    public function store(Request $request): RedirectResponse //esta funcion es para guardar los datos del nuevo usuario
    { 
        $request->validate([
            //se valida que los campos no esten vacios
            //se valida que el documento y username sean unicos en la tabla users
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'documento' => 'required|string|max:20|unique:users,documento',
            'username' => 'required|string|max:20|unique:users,username',
            'telefono' => 'required|string|max:15',
            'email' => 'required|string|lowercase|email|max:100|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // ROL POR DEFECTO PARA REGISTRO PÚBLICO: CLIENTE (ID 3)
        $rolCliente = Rol::whereIn('descripcion', ['Cliente', 'cliente'])->first();
        if (!$rolCliente) {
            Rol::firstOrCreate(['id' => 1], ['descripcion' => 'admin']);
            Rol::firstOrCreate(['id' => 2], ['descripcion' => 'vendedor']);
            $rolCliente = Rol::firstOrCreate(['id' => 3], ['descripcion' => 'cliente']);
        }

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'documento' => trim($request->documento),
            'username' => $request->username,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $rolCliente->id,
            'puede_operar_caja' => false,
            'estado' => 'Activo',
        ]);

        event(new Registered($user));

        // NO se autentica automáticamente: el usuario debe iniciar sesión manualmente.
        return redirect()->route('login')
            ->with('status', '¡Registro exitoso! Ya puedes iniciar sesión.');
    }
}
