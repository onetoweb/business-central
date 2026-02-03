<?php

namespace Onetoweb\BusinessCentral;

use DateTime;

/**
 * Token.
 */
class Token
{
    /**
     * @var string
     */
    private $accessToken;
    
    /**
     * @var DateTime
     */
    private $expires;
    
    /**
     * @param string $accessToken
     * @param DateTime $expires
     */
    public function __construct(string $accessToken, DateTime $expires)
    {
        $this->accessToken = $accessToken;
        $this->expires = $expires;
    }
    
    /**
     * @return string
     */
    public function getAccessToken():  string
    {
        return $this->accessToken;
    }
    
    /**
     * @return DateTime
     */
    public function getExpires(): DateTime
    {
        return $this->expires;
    }
    
    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return (new DateTime() > $this->expires);
    }
}
