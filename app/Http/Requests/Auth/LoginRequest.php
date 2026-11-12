<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required_without:email', 'nullable', 'string'],
            'email'    => ['required_without:username', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Permite iniciar sesión con "username" (documento/usuario) O email,
     * y exige que el usuario tenga estado = 'Activo'.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = (string) ($this->input('username') ?: $this->input('email'));
        $password = (string) $this->input('password');

        // Si lo que escribió parece un correo, busca por email; si no, por username.
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 1. Intentar autenticar credenciales activas
        $credentials = [
            $field => $login,
            'password' => $password,
            'estado' => 'Activo',
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Solo si la contraseña es correcta pero la cuenta está inactiva, se informa al usuario legítimo
            $user = \App\Models\User::where($field, $login)->first();
            if ($user && $user->estado === 'Inactivo' && Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    'username' => 'Esta cuenta se encuentra inactiva. Por favor, ponte en contacto con el administrador para reactivar tu acceso.',
                ]);
            }

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')) . '|' . $this->ip());
    }
}
