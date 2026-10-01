<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('accounts.login');
    }

    /** O campo de login aceita nome de usuário ou e-mail (sem diferenciar maiúsculas). */
    public function login(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['username' => 'usuário ou e-mail', 'password' => 'senha']);

        $login = trim($dados['username']);
        if (str_contains($login, '@')) {
            $porEmail = Usuario::whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first();
            if ($porEmail) {
                $login = $porEmail->username;
            }
        }

        $credenciais = ['username' => $login, 'password' => $dados['password'], 'is_active' => true];

        if (! Auth::attempt($credenciais)) {
            throw ValidationException::withMessages([
                'username' => 'Por favor, entre com um usuário e senha corretos. Note que ambos os campos diferenciam maiúsculas e minúsculas.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('lembrar', $request->boolean('lembrar'));

        Usuario::whereKey(Auth::id())->update(['last_login' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
