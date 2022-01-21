<?php

namespace RZ\MixedFeed\Graph;

class AccessToken
{
    protected string $accessToken = '';

    protected string $tokenType = '';

    protected int $expiresIn = 0;

    final public function __construct(string $accessToken, string $tokenType = '', int $expiresIn = 0)
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return static
     */
    public static function fromArray(array $payload)
    {
        return new static(
            $payload['access_token'],
            $payload['token_type'],
            $payload['expires_in']
        );
    }

    public function __toString()
    {
        return $this->accessToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}
