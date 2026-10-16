<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Contracts\IAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly IAuthService $auth) {}

    public function login(): Response
    {
        return Inertia::render('Auth/Login', [
            'devLogin' => app()->isLocal(),
            'googleConfigured' => filled(config('services.google.client_id')),
        ]);
    }

    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('error', 'Não foi possível entrar com o Google. Tente de novo.');
        }

        $user = $this->auth->loginWithGoogle($google->getId(), $google->getEmail(), $google->getName() ?? $google->getEmail(), $google->getAvatar());
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Só local: entra sem Google para testar com e-mails pré-cadastrados. */
    public function devLogin(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 404);

        $data = $request->validate(['email' => ['required', 'email'], 'name' => ['nullable', 'string', 'max:120']]);
        Auth::login($this->auth->loginForDevelopment($data['email'], $data['name'] ?? null), remember: true);
        $request->session()->regenerate();

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
