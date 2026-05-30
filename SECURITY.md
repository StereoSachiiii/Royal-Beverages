# Security Policy

This document outlines the security mechanisms implemented in the Royal Beverages e-commerce application.

## 1. CSRF Protection
Cross-Site Request Forgery (CSRF) is mitigated using a robust token-based approach. 
- A cryptographically secure random token is generated upon session initialization and stored in `$_SESSION['csrf_token']`.
- The token is embedded in all state-mutating requests (POST, PUT, DELETE) either via hidden input fields or the `X-CSRF-Token` HTTP header.
- The `CSRFMiddleware` enforces token validation for all state-mutating API routes, throwing a 403 Forbidden exception on failure.

## 2. Session Fixation Prevention
Session fixation attacks are prevented by aggressively rotating session IDs.
- Upon critical state changes (e.g., login, logout), `session_regenerate_id(true)` is called.
- This invalidates the old session ID and generates a new one, ensuring that a session ID obtained by an attacker prior to login cannot be used to hijack the authenticated session.

## 3. SQL Injection Prevention
All database interactions use **PDO (PHP Data Objects)** with Prepared Statements.
- Dynamic values are never concatenated directly into SQL queries.
- Inputs are bound to parameters (`:param_name`), which PDO safely escapes and formats.
- Strict type binding (e.g., `PDO::PARAM_INT`, `PDO::PARAM_STR`) is enforced across all Repository methods.

## 4. Password Storage
User passwords are encrypted using the industry-standard **BCrypt** hashing algorithm.
- Passwords are hashed using PHP's native `password_hash($password, PASSWORD_BCRYPT)`.
- Hashes are verified using `password_verify()`.
- Plaintext passwords are never stored, logged, or exposed in the application data.

## 5. Rate Limiting
To prevent brute-force and Denial-of-Service (DoS) attacks on critical endpoints (e.g., authentication, API routes), a rate limiting middleware is applied.
- The `RateLimitMiddleware` restricts the number of requests a user or IP can make within a specified time window.
- Scalability is supported by a conditional Redis integration; falling back to session-based limits if Redis is unavailable.
