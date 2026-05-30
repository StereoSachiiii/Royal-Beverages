<?php
/**
 * Logout Handler
 * Destroys session and redirects to home
 */
require_once dirname(dirname(dirname(__DIR__))) . "/src/Core/bootstrap.php";
require_once dirname(dirname(__DIR__)) . "/config/urls.php";

$session = \App\Core\Session::getInstance();

// Destroy session
$session->logout();

// Redirect to home
header('Location: ' . getPageUrl('home'));
exit;
