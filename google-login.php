<?php
// google-login.php - Redirect ke Google OAuth
session_start();

require_once 'vendor/autoload.php';

// Konfigurasi Google Client
$client_id = "364786939364-0ggfnrqhufo2n2s3ma0h8i83bkmvoj6f.apps.googleusercontent.com";
$client_secret = "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";
$redirect_uri = "http://" . $_SERVER['HTTP_HOST'] . "/google-callback.php";

// Create Google Client
$client = new Google\Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("email");
$client->addScope("profile");
$client->setPrompt("select_account"); // Biar user bisa pilih akun

// Generate auth URL dan redirect
$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();
?>