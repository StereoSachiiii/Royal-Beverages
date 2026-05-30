<?php
declare(strict_types=1);

namespace App\Core;

class CSRF{

    public Session $session;
    /** @var int<1, max> */
    private int $csrfTokenLength = 32; // bytes
    public function __construct(Session $session){
        $this->session = $session; 
    }

    public function generateToken():string {
        $token = bin2hex(random_bytes($this->csrfTokenLength));
        $this->session->set('csrf_token',$token);
        return $token;
    }

public function getToken(): string {
    $token = $this->session->get('csrf_token');
    if (!$token) {
        $token = $this->generateToken();
    }
    return $token;
}

    /**
     * Summary of validateToken
     * returns True if the session token is valid
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token):bool {
        //check if $token is null 
        $currentCSRFToken = $this->getToken();
        if($currentCSRFToken){
            return hash_equals( $currentCSRFToken, $token);
        }else{
            return false;

        }

    }

}
?>
