<?php
require_once 'vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->setRedirectUri('http://192.168.140.141/API SCORE/api/oauth2callback.php');
$client->addScope(Google_Service_Sheets::SPREADSHEETS);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if(!isset($token['error'])){
        $_SESSION['access_token'] = $token;
        header('Location: ../export.html');
        exit;
    }
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
    if ($client->isAccessTokenExpired()) {
        unset($_SESSION['access_token']);
        header('Location: ' . $client->createAuthUrl());
        exit;
    }
} else {
    header('Location: ' . $client->createAuthUrl());
    exit;
}
