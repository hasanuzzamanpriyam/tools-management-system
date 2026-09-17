<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_provider_results_in_spa_fallback(): void
    {
        // Unknown provider should not match the OAuth route and fall through to SPA
        $response = $this->get('/auth/facebook/redirect');

        // Should return the SPA (200) not a 404 from controller
        $response->assertStatus(200);
    }

    public function test_github_redirect_redirects_to_oauth_provider(): void
    {
        $response = $this->get('/auth/github/redirect');

        $this->assertRedirect();
        $this->assertStringContainsString('github.com/login/oauth/authorize', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=', $response->headers->get('Location'));
        $this->assertStringContainsString('redirect_uri=', $response->headers->get('Location'));
        $this->assertStringContainsString('state=', $response->headers->get('Location'));
        $this->assertStringContainsString('scope=read:user', $response->headers->get('Location'));

        // Check that state was stored in session
        $this->assertNotNull(Session::get('oauth_state'));
    }

    public function test_google_redirect_redirects_to_oauth_provider(): void
    {
        $response = $this->get('/auth/google/redirect');

        $this->assertRedirect();
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=', $response->headers->get('Location'));
        $this->assertStringContainsString('redirect_uri=', $response->headers->get('Location'));
        $this->assertStringContainsString('state=', $response->headers->get('Location'));
        $this->assertStringContainsString('response_type=code', $response->headers->get('Location'));
        $this->assertStringContainsString('scope=openid%20email%20profile', $response->headers->get('Location'));
        $this->assertStringContainsString('access_type=offline', $response->headers->get('Location'));
        $this->assertStringContainsString('prompt=consent', $response->headers->get('Location'));

        // Check that state was stored in session
        $this->assertNotNull(Session::get('oauth_state'));
    }

    public function test_callback_with_invalid_state_redirects_to_login_with_error(): void
    {
        // Set a different state in session
        Session::put('oauth_state', 'expected_state');

        $response = $this->get('/auth/github/callback?state=different_state&code=test_code');

        $this->assertRedirectToRoute('login');
        $this->assertStringContainsString('oauth_error=invalid_state', $response->headers->get('Location'));

        // State should be cleared from session
        $this->assertNull(Session::get('oauth_state'));
    }
}