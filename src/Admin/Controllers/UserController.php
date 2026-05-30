<?php
declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Core\Session;
use App\Admin\Exceptions\ValidationException;
use App\Admin\Exceptions\NotFoundException;
use App\Admin\Exceptions\UnauthorizedException;
use App\Admin\Exceptions\DatabaseException;
use App\Admin\Exceptions\DuplicateException;

/**
 * UserController
 *
 * FIX: Previously bypassed UserService entirely (new UserRepository() + new Session()
 * directly in constructor). Now properly injected via DI container.
 */
class UserController extends BaseController
{
    public function __construct(
        private UserService $service,
        private Session $session,
    ) {}

    // ====================================================================
    // AUTH
    // ====================================================================

    public function register(array $data): array
    {
        return $this->handle(function () use ($data) {
            $user = $this->service->register($data);

            $this->session->login([
                'id'       => $user['id'],
                'name'     => $user['name'],
                'email'    => $user['email'],
                'is_admin' => $user['is_admin'] ?? false,
            ]);

            return $this->success('Registered successfully', $user, 201);
        });
    }

    public function login(array $data): array
    {
        return $this->handle(function () use ($data) {
            $user = $this->service->login($data);

            $this->session->login([
                'id'       => $user['id'],
                'name'     => $user['name'],
                'email'    => $user['email'],
                'is_admin' => $user['is_admin'] ?? false,
            ]);

            return $this->success('Login successful', $user);
        });
    }

    public function logout(): array
    {
        return $this->handle(function () {
            $this->session->logout();
            return $this->success('Logged out successfully');
        });
    }

    public function googleRedirect(): array
    {
        $config = $GLOBALS['app_config']['security']['oauth'] ?? [];
        $clientId = $config['google_client_id'] ?? '';
        $redirectUri = $config['google_redirect_uri'] ?? '';

        if (empty($clientId) || empty($redirectUri)) {
            return $this->error('Google OAuth not configured', 500);
        }

        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    public function googleCallback(array $queryParams): array
    {
        $code = $queryParams['code'] ?? null;
        $state = $queryParams['state'] ?? null;
        
        $savedState = $this->session->get('oauth_state');
        $this->session->set('oauth_state', null);

        if (!$state || $state !== $savedState) {
            return $this->error('Invalid OAuth state', 400); 
        }

        if (!$code) {
           return $this->error('Missing OAuth code', 400);
        }

        $config = $GLOBALS['app_config']['security']['oauth'] ?? [];
        $clientId = $config['google_client_id'] ?? '';
        $clientSecret = $config['google_client_secret'] ?? '';
        $redirectUri = $config['google_redirect_uri'] ?? '';
        
        $envUrl = rtrim($GLOBALS['app_config']['urls']['app'] ?? '', '/');
        $fallbackUrl = defined('APP_BASE_URL') ? rtrim(APP_BASE_URL, '/') : '';
        $frontendSuccess = ltrim($config['frontend_success_url'] ?? '/public/myaccount/dashboard.php', '/');
        $frontendUrl = ($envUrl ?: $fallbackUrl) . '/' . $frontendSuccess;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri
        ]));
        $tokenResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return $this->error('Failed to exchange Google token', 500, ['details' => $tokenResponse]);
        }

        $tokenData = json_decode((string)$tokenResponse, true);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return $this->error('No access token received', 500);
        }

        $chUser = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($chUser, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chUser, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        $userResponse = curl_exec($chUser);
        $userHttpCode = curl_getinfo($chUser, CURLINFO_HTTP_CODE);
        curl_close($chUser);

        if ($userHttpCode !== 200) {
             return $this->error('Failed to fetch Google profile', 500);
        }

        $googleProfile = json_decode((string)$userResponse, true);

        return $this->handle(function() use ($googleProfile, $frontendUrl) {
             $user = $this->service->handleGoogleOAuth($googleProfile);
             
             $this->session->login([
                 'id'       => $user['id'],
                 'name'     => $user['name'],
                 'email'    => $user['email'],
                 'is_admin' => $user['is_admin'] ?? false,
             ]);
             
             header('Location: ' . $frontendUrl);
             exit;
        });
    }

    // ====================================================================
    // PROFILE
    // ====================================================================

    public function getProfile(int $userId): array
    {
        return $this->handle(function () use ($userId) {
            $user = $this->service->getProfile($userId);
            return $this->success('Profile retrieved', $user);
        });
    }

    public function updateProfile(int $userId, array $data): array
    {
        return $this->handle(function () use ($userId, $data) {
            $user = $this->service->updateProfile($userId, $data);

            // Sync session if it's the logged-in user
            if ($this->session->get('user_id') === $userId) {
                $this->session->set('name', $user['name']);
                if (isset($data['email'])) {
                    $this->session->set('email', $user['email']);
                }
            }

            return $this->success('Profile updated', $user);
        });
    }

    public function anonymizeUser(int $userId): array
    {
        return $this->handle(function () use ($userId) {
            $this->service->anonymizeUser($userId);
            return $this->success('User anonymized successfully');
        });
    }

    // ====================================================================
    // ADDRESSES
    // ====================================================================

    public function createAddress(int $userId, array $data): array
    {
        return $this->handle(function () use ($userId, $data) {
            $id = $this->service->createAddress($userId, $data);
            return $this->success('Address created', ['address_id' => $id], 201);
        });
    }

    public function getAddresses(int $userId, ?string $type = null): array
    {
        return $this->handle(function () use ($userId, $type) {
            $addresses = $this->service->getAddresses($userId, $type);
            return $this->success('Addresses retrieved', $addresses);
        });
    }

    public function updateAddress(int $addressId, array $data): array
    {
        return $this->handle(function () use ($addressId, $data) {
            $this->service->updateAddress($addressId, $data);
            return $this->success('Address updated');
        });
    }

    public function deleteAddress(int $addressId): array
    {
        return $this->handle(function () use ($addressId) {
            $this->service->deleteAddress($addressId);
            return $this->success('Address deleted');
        });
    }

    // ====================================================================
    // ADMIN
    // ====================================================================

    public function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($limit, $offset) {
            $users = $this->service->getAllUsers($limit, $offset);
            return $this->success('Users retrieved', $users);
        });
    }

    public function getAllEnriched(int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($limit, $offset) {
            $users = $this->service->getAllPaginated($limit, $offset);
            return $this->success('Users retrieved', $users);
        });
    }

    public function searchUsers(string $query, int $limit = 50, int $offset = 0): array
    {
        return $this->handle(function () use ($query, $limit, $offset) {
            $users = $this->service->searchUsers($query, $limit, $offset);
            return $this->success('Search results retrieved', $users);
        });
    }

    public function getByIdEnriched(int $id): array
    {
        return $this->handle(function () use ($id) {
            $user = $this->service->getByIdEnriched($id);
            return $this->success('User details retrieved', $user);
        });
    }

    public function count(): array
    {
        return $this->handle(function () {
            $count = $this->service->count();
            return $this->success('User count retrieved', ['count' => $count]);
        });
    }

    public function delete(int $id, bool $hard = false): array
    {
        return $this->handle(function () use ($id, $hard) {
            if ($hard) {
                $this->service->hardDelete($id);
                return $this->success('User permanently deleted');
            }
            $this->service->softDelete($id);
            return $this->success('User deleted');
        });
    }
}
