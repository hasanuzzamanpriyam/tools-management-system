<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OAuth2Service
{
    public function redirectUrl(string $provider): string
    {
        $config = $this->config($provider);
        $state = Str::random(40);

        session()->put('oauth_state', $state);

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'state' => $state,
        ];

        return match ($provider) {
            'github' => 'https://github.com/login/oauth/authorize?'.http_build_query([
                ...$params,
                'scope' => 'read:user',
            ]),
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                ...$params,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]),
        };
    }

    public function user(string $provider, string $code): array
    {
        $config = $this->config($provider);
        $token = $this->token($provider, $config, $code);
        $profile = $this->profile($provider, $token);

        return $this->map($provider, $profile);
    }

    private function config(string $provider): array
    {
        $config = config("services.{$provider}");

        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect'])) {
            throw new RuntimeException("OAuth provider {$provider} is not configured.");
        }

        return $config;
    }

    private function token(string $provider, array $config, string $code): string
    {
        return match ($provider) {
            'github' => $this->githubToken($config, $code),
            'google' => $this->googleToken($config, $code),
        };
    }

    private function githubToken(array $config, string $code): string
    {
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $code,
                'redirect_uri' => $config['redirect'],
            ])->throw()->json();

        return $response['access_token'];
    }

    private function googleToken(array $config, string $code): string
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect'],
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        return $response['access_token'];
    }

    private function profile(string $provider, string $token): array
    {
        return match ($provider) {
            'github' => $this->githubProfile($token),
            'google' => $this->googleProfile($token),
        };
    }

    private function githubProfile(string $token): array
    {
        $user = Http::withToken($token)
            ->get('https://api.github.com/user')->throw()->json();

        if (empty($user['email'])) {
            $emails = Http::withToken($token)
                ->get('https://api.github.com/user/emails')->throw()->json();

            $primary = collect($emails)->firstWhere('primary', true) ?? $emails[0] ?? null;
            $user['email'] = $primary['email'] ?? null;
        }

        return $user;
    }

    private function googleProfile(string $token): array
    {
        return Http::withToken($token)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')->throw()->json();
    }

    private function map(string $provider, array $profile): array
    {
        return match ($provider) {
            'github' => [
                'id' => (string) ($profile['id'] ?? ''),
                'name' => $profile['name'] ?? $profile['login'] ?? 'GitHub User',
                'email' => $profile['email'] ?? null,
                'avatar_url' => $profile['avatar_url'] ?? null,
            ],
            'google' => [
                'id' => (string) ($profile['sub'] ?? ''),
                'name' => $profile['name'] ?? 'Google User',
                'email' => $profile['email'] ?? null,
                'avatar_url' => $profile['picture'] ?? null,
            ],
        };
    }
}