<?php

namespace Onetoweb\BusinessCentral;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as GuzzleCLient;
use Onetoweb\BusinessCentral\Token;
use Onetoweb\BusinessCentral\Exception\TokenException;

/**
 * Business Central Api Client.
 */
class Client
{
    /**
     * Base href
     */
    public const BASE_HREF = 'https://api.businesscentral.dynamics.com/v2.0/%s/';
    public const AUTHORIZE_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize';
    
    /**
     * Methods.
     */
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    
    /**
     * @var string
     */
    private $clientId;
    
    /**
     * @var string
     */
    private $secret;
    
    /**
     * @var string
     */
    private $tenantId;
    
    /**
     * @var callable
     */
    private $updateTokenCallback;
    
    /**
     * @var Token
     */
    private $token;
    
    /**
     * @param string $clientId
     * @param string $secret
     * @param string $tenantId
     */
    public function __construct(string $clientId, string $secret, string $tenantId)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->tenantId = $tenantId;
    }
    
    /**
     * @param string $redirectUri
     * @param ?string $state = null
     * 
     * @return string
     */
    public function getAuthorizeUrl(string $redirectUri, ?string $state = null): string
    {
        $query = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'https://api.businesscentral.dynamics.com/.default',
        ];
        
        if ($state !== null) {
            
            $query = array_merge($query, [
                'state' => $state
            ]);
        }
        
        return sprintf(self::AUTHORIZE_URL, $this->tenantId).'?'.http_build_query($query);
    }
    
    /**
     * @param Token $token
     * 
     * @return void
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
    
    /**
     * @return ?Token
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }
    
    /**
     * @param callable $updateTokenCallback
     */
    public function setUpdateTokenCallback(callable $updateTokenCallback)
    {
        $this->updateTokenCallback = $updateTokenCallback;
    }
    
    /**
     * @param string $endpoint
     * 
     * @return string
     */
    public function getUrl(string $endpoint): string
    {
        return sprintf(self::BASE_HREF, $this->tenantId) . '/' . ltrim($endpoint, '/');
    }
    
    /**
     * @param string $endpoint
     * @param array $query = []
     * 
     * @return array|null
     */
    public function get(string $endpoint, array $query = []): ?array
    {
        return $this->request(self::METHOD_GET, $endpoint, [], $query);
    }
    
    /**
     * @param string $endpoint
     * @param array $data = []
     * 
     * @return array|null
     */
    public function post(string $endpoint, array $data = []): ?array
    {
        return $this->request(self::METHOD_POST, $endpoint, $data);
    }
    
    /**
     * @param string $code
     * 
     * @return void
     */
    public function requestAccessToken(string $code): void
    {
        // request access token request
        
        $tokenArray = [];
        
        $this->updateToken($tokenArray);
    }
    
    /**
     * @return void
     */
    public function refreshAccessToken(): void
    {
        // refresh access token request
        
        $tokenArray = [];
        
        $this->updateToken($tokenArray);
    }
    
    /**
     * @param array $tokenArray
     */
    public function updateToken(array $tokenArray): void
    {
        // get expires DateTime
        $expires = (new DateTime())
            ->setTimestamp(time() + $tokenArray['expires_in'])
        ;
        
        // update token
        $this->token = new Token(
            $tokenArray['access_token'],
            $tokenArray['refresh_token'],
            $expires
        );
        
        ($this->updateTokenCallback)($this->token);
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     * 
     * @throws TokenException if no token was set
     * 
     * @return array|null
     */
    public function request(string $method, string $endpoint, array $data = [], array $query = []): ?array
    {
        if ($this->token === null) {
            throw new TokenException('not token was set use setToken to set a token');
        }
        
        if ($this->token->isExpired()) {
            $this->refreshAccessToken();
        }
        
        // build options
        $options = [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Bearer' => $this->token->getAccessToken(),
                'Data-Access-Intent' => ($method === self::METHOD_GET) ? 'ReadOnly' : 'ReadWrite',
            ],
            RequestOptions::JSON => $data,
            RequestOptions::QUERY => $query,
        ];
        
        // make request
        $response = (new GuzzleCLient())->request($method, $this->getUrl($endpoint), $options);
        
        // decode json
        $json = json_decode($response->getBody()->getContents(), true);
        
        return $json;
    }
}
