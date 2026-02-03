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
    use Symfony\Component\HttpFoundation\Session\Session;
    
    // start sessions for token storage
    $session = new Session();
    $session->start();
    
    // params
    $clientId = '{client_id}';
    $secret = '{secret}';
    $tenantId = '{tenant_id}';
    
    // setup client
    $client = new Client($clientId, $secret, $tenantId);
    
    // set update token callback
    $client->setUpdateTokenCallback(function(Token $token) use ($session) {
        
        // store token
        $session->set('token', [
            'access_token' => $token->getAccessToken(),
            'expires' => $token->getExpires()
        ]);
        
    });
    
    if ($session->has('token')) {
        
        // load token from storage
        $token = $session->get('token');
        
        $client->setToken(new Token(
            $token['access_token'],
            $token['expires']
        ));
        
    }
