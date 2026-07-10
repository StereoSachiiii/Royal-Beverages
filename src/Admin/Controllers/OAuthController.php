<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Services\UserService;

class OAuthController extends BaseController
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(
        private UserService $userService,
        private Session $session
    ) {
        $this->clientId = getenv('GOOGLE_CLIENT_ID') ?: '892174316353-0e5rlmnujn37u6vup7a1m2is1ft89rvq.apps.googleusercontent.com';
        $this->clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET';
        
        $protocol = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) 
            ? $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' 
            : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://');
            
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $this->redirectUri = $protocol . $host . '/api/v1/oauth/google/callback';
    }

    public function redirect(Request $request): void
    {
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    public function callback(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $state = $request->getQuery('state');
            $code = $request->getQuery('code');
            $sessionState = $this->session->get('oauth_state');

            if (!$state || $state !== $sessionState) {
                return $this->error('Invalid state parameter', 400);
            }

            if (!$code) {
                return $this->error('Authorization code missing', 400);
            }

            // 1. Exchange code for access token
            $tokenPost = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($tokenPost),
                    'ignore_errors' => true
                ]
            ]);

            $tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
            $tokenData = json_decode($tokenResponse, true);

            if (!isset($tokenData['access_token'])) {
                return $this->error('Failed to obtain access token', 400, $tokenData);
            }

            // 2. Fetch user profile from Google
            $userContext = stream_context_create([
                'http' => [
                    'header' => 'Authorization: Bearer ' . $tokenData['access_token']
                ]
            ]);
            
            $userResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, $userContext);
            $googleProfile = json_decode($userResponse, true);

            if (!isset($googleProfile['id'])) {
                return $this->error('Failed to fetch user profile', 400);
            }

            // 3. Handle login/registration
            $user = $this->userService->handleGoogleOAuth($googleProfile);

            // Log user in
            $this->session->set('user_id', $user['id']);
            $this->session->set('is_admin', $user['is_admin']);

            return $this->success('Successfully logged in via Google', $user);
        });
    }
}
