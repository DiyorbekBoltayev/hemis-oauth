<?php
session_start();
// Autoload files using the Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';
//$employeeProvider = new \League\OAuth2\Client\Provider\GenericProvider([
//    'clientId'                => '8',
//    'clientSecret'            => 'Vt5dnZtzK_v3vzs0ycsV2uLzrh7zicZUrz4TEiOI',
//    'redirectUri'             => 'http://hemis-oauth-test.lc/index.php',
//    'urlAuthorize'            => 'https://univer.hemis.uz/oauth/authorize',
//    'urlAccessToken'          => 'https://univer.hemis.uz/oauth/access-token',
//    //available fields: id,uuid,type,roles,name,login,email,phone,picture,firstname,surname,patronymic,birth_date,university_id,groups
//    'urlResourceOwnerDetails' => 'https://univer.hemis.uz/oauth/api/user?fields=id,uuid,type,roles,name,login,picture,email,university_id,phone'
//]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$employeeProvider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => '6',
    'clientSecret'            => 'sxN-JeASgdqXvu5oXBUWDmO51QI-5MuK6DhzZ9cV',
    'redirectUri'             => 'https://test-bmi-d306051c0798.herokuapp.com',
    'urlAuthorize'            => 'https://student.ubtuit.uz/oauth/authorize',
    'urlAccessToken'          => 'https://student.ubtuit.uz/oauth/access-token',
    'urlResourceOwnerDetails' => 'https://student.ubtuit.uz/oauth/api/user?fields=id,uuid,type,name,login,picture,email,university_id,phone,groups'
    ,'verify'                  => false,
]);
$guzzyClient = new GuzzleHttp\Client([
    'defaults' => [
        \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 5,
        \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => true],
    \GuzzleHttp\RequestOptions::VERIFY => false,
]);

$employeeProvider->setHttpClient($guzzyClient);


// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {
    if (isset($_GET['start'])) {
        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $employeeProvider->getAuthorizationUrl();

        // Get the state generated for you and store it to the session.
        $_SESSION['oauth2state'] = $employeeProvider->getState();

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;
    } else {
        echo "<a href='/?start=1'>Authorize with HEMIS</a>";
    }
// Check given state against previously stored one to mitigate CSRF attack
} else if (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }

    exit('Invalid state');

} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $employeeProvider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo "<p>Access Token: <b>{$accessToken->getToken()}</b></p>";
        echo "<p>Refresh Token: <b>{$accessToken->getRefreshToken()}</b></p>";
        echo "Expired in: <b>" . date('m/d/Y H:i:s', $accessToken->getExpires()) . "</b></p>";
        echo "Already expired: <b>" . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "</b></p>";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $employeeProvider->getResourceOwner($accessToken);

        echo "<pre>" . print_r($resourceOwner->toArray(), true) . "</pre>";

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage()."<br>".$e->getTraceAsString()."<br>".$e->getPrevious()."<br>".$e->getCode()."<br>".$e->getLine()."<br>".$e->getFile());
    }
}