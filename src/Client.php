<?php

namespace Onetoweb\BusinessCentral;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as GuzzleCLient;
use Onetoweb\BusinessCentral\Token;
use DateTime;

/**
 * Business Central Api Client.
 */
class Client
{
    /**
     * Base href
     */
    public const BASE_HREF = 'https://api.businesscentral.dynamics.com/v2.0/%s';
    public const TOKEN_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';
    
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
     * @return string
     */
    public function getTokenUrl(): string
    {
        return sprintf(self::TOKEN_URL, $this->tenantId);
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
     * @return void
     */
    public function requestAccessToken(): void
    {
        // request access token request
        $options = [
            RequestOptions::FORM_PARAMS => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->secret,
                'scope' => 'https://api.businesscentral.dynamics.com/.default',
            ],
        ];
        
        // make request
        $response = (new GuzzleCLient())->post($this->getTokenUrl(), $options);
        
        // decode json
        $tokenArray = json_decode($response->getBody()->getContents(), true);
        
        $this->updateToken($tokenArray);
    }
    
    /**
     * @param array $tokenArray
     * 
     * @return void
     */
    private function updateToken(array $tokenArray): void
    {
        // get expires DateTime
        $expires = (new DateTime())
            ->setTimestamp(time() + $tokenArray['expires_in'])
        ;
        
        // update token
        $this->token = new Token(
            $tokenArray['access_token'],
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
     * @return array|null
     */
    public function request(string $method, string $endpoint, array $data = [], array $query = []): ?array
    {
        if (
            $this->token === null
            or $this->token->isExpired()
        ) {
            $this->requestAccessToken();
        }
        
        // build options
        $options = [
            RequestOptions::HEADERS => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => "Bearer {$this->token->getAccessToken()}",
            ],
            RequestOptions::JSON => $data,
            RequestOptions::QUERY => $query,
        ];
        
        // make request
        $response = (new GuzzleCLient())->request($method, $this->getUrl($endpoint), $options);
        
        // get contents
        $contents = $response->getBody()->getContents();
        
        // decode json
        $json = json_decode($contents, true);
        
        return $json;
    }
}
