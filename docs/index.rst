.. title:: Index

Index
=====

.. contents::
    :local:

===========
Basic Usage
===========

Setup

.. code-block:: php
    
    require 'vendor/autoload.php';
    
    use Onetoweb\BusinessCentral\Client;
    use Onetoweb\BusinessCentral\Token;
    
    session_start();
    
    // params
    $clientId = 'client_id';
    $secret = 'secret';
    $tenantId = 'tenant_id';
    
    // setup client
    $client = new Client($clientId, $secret, $tenantId);
    
    // set update token callback
    $client->setUpdateTokenCallback(function(Token $token) {
        
        // store token
        $_SESSION['token'] = [
            'access_token' => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires' => $token->getExpires()
        ];
    });
    
    /**
     * Authorize with oAuth 2.0 Authentication Workflow
     */
    
    if (isset($_SESSION['token'])) {
        
        // load token from storage
        $client->setToken(new Token(
            $_SESSION['token']['access_token'],
            $_SESSION['token']['refresh_token'],
            $_SESSION['token']['expires']
        ));
        
    } elseif (isset($_GET['code'])) {
        
        // exchange code for access token
        $client->requestAccessToken($_GET['code']);
        
    } else {
        
        // get redirect url 
        $redirectUrl = 'https://www.example.com/';
        
        // get authorize url
        $authorizeUrl = $client->getAuthorizeUrl($redirectUrl);
        
        // present authorize url to begin the Authentication process
        printf('<a href="%1$s">%1$s</a>', $authorizeUrl);
    }
