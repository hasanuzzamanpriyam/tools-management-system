<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\OAuth2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use App\Http\Controllers\Controller;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, ['github', 'google'], true), 404);

        try {
            $url = app(OAuth2Service::class)->redirectUrl($provider);
        } catch (RuntimeException) {
            return redirect('/login?oauth_error=social_not_configured');
        }

        return redirect()->away($url);
    }

    public function callback(string $provider, Request $request)
    {
        abort_unless(in_array($provider, ['github', 'google'], true), 404);

        $state = $request->query('state');
        $expected = session('oauth_state');

        if (! $state || ! $expected || ! hash_equals($state, $expected)) {
            return redirect('/login?oauth_error=invalid_state');
        }

        session()->forget('oauth_state');

        try {
            $data = app(OAuth2Service::class)->user($provider, $request->query('code'));
        } catch (RuntimeException) {
            return redirect('/login?oauth_error=social_not_configured');
        } catch (\Exception) {
            return redirect('/login?oauth_error=oauth_failed');
        }

        if (empty($data['email'])) {
            return redirect('/login?oauth_error=missing_email');
        }

        $user = User::where('provider', $provider)->where('provider_id', $data['id'])->first();

        if (! $user) {
            $user = User::where('email', $data['email'])->first();
        }

        if ($user) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $data['id'],
                'provider_avatar_url' => $data['avatar_url'],
                'email_verified_at' => now(),
            ]);
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(40)),
                'role' => User::ROLE_USER,
                'provider' => $provider,
                'provider_id' => $data['id'],
                'provider_avatar_url' => $data['avatar_url'],
                'email_verified_at' => now(),
                'referral_code' => $this->uniqueReferralCode(),
            ]);
        }

        if (! $user->is_active) {
            return redirect('/login?oauth_error=account_deactivated');
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return redirect(config('app.url').'/oauth/callback?token='.$token.'&email='.urlencode($user->email));
    }

    private function uniqueReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}