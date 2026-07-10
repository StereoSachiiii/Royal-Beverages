<?php
declare(strict_types=1);

namespace App\Core;

class Session {
    private static ?Session $instance = null; 

    private int $timeout; 
    private CSRF $csrf; 

    private function __construct(int $timeout = 86400) { 
        $this->timeout = $timeout;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // INACTIVITY TIMEOUT
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $this->timeout) {
            $this->destroy(); 
            $this->initGuest();
        }
        $_SESSION['last_activity'] = time(); 

        // GUEST USER LOGIC
        if (!isset($_SESSION['user_id'])) {
            $this->initGuest();
        }

        // CSRF TOKEN
        $this->csrf = new CSRF($this);
        $this->csrf->getToken(); 
    }

        
    // SESSION ACCESSORS
    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    // LOGIN & LOGOUT
    /**
     * Summary of login
     * @param array{id:int,name:string,email:null|string,is_admin:bool|null} $userData
     * @return void
     */
    public function login(array $userData): void {
        session_regenerate_id(true); // prevent fixation

        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['name'] = $userData['name'];
        $_SESSION['email'] = $userData['email'] ?? null;
        $_SESSION['profile_image_url'] = $userData['profile_image_url'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['is_admin'] = $userData['is_admin'] ?? false;

        //not to be saved to db
        $_SESSION['session_id'] = session_id();
     
        // Optional: reset guest info
        unset($_SESSION['guest_id']);
        unset($_SESSION['is_guest']);
    }

    public function logout(): void {
        $this->destroy();
        self::$instance = null;
        $this->initGuest();
    }

    public function isLoggedIn(): bool {
        return $this->get('logged_in', false) === true;
    }



    public function isAdmin(): bool {
        $isAdmin = $this->get('is_admin', false);
        return $isAdmin === true || $isAdmin === 1 || $isAdmin === '1';
    }

    public function getUserId(): mixed {
        return $this->isLoggedIn() ? $this->get('user_id') : $this->get('guest_id');
    }

    public function getUsername(): string {
        return (string)$this->get('name', 'Guest');
    }

    public function getEmail(): ?string {
        return $this->get('email');
    }

    public function getProfileImageUrl(): ?string {
        return $this->get('profile_image_url');
    }

    public function getCsrfInstance(): CSRF {
        // @phpstan-ignore isset.initializedProperty
        if (!isset($this->csrf)) {
            throw new \RuntimeException("CSRF instance is undefined in Session.");
        }
        return $this->csrf;
    }

    public function getSessionID(): string {
        return (string)($_SESSION['session_id'] ?? '');
    }

    // GUEST USER INITIALIZATION
    private function initGuest(): void {
        if (!isset($_SESSION['guest_id'])) {
            $_SESSION['is_guest'] = true;
            $_SESSION['guest_id'] = 'guest_' . bin2hex(random_bytes(16)); // secure random ID
            $_SESSION['username'] = 'Guest';
        }
    }

    

    public function isGuest(): bool {
        return $this->get('is_guest', false) === true;
    }

    // DESTROY SESSION
    public function destroy(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie((string)session_name(), '', time() - 3600,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }



    public static function getInstance(): Session {

        if(self::$instance===null)
        {
            self::$instance = new Session();
            return self::$instance;
        }
        return self::$instance;


}
}
