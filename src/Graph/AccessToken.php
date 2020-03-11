<?php
declare(strict_types=1);

namespace RZ\MixedFeed\Graph;

class AccessToken
{
    /**
     * @var string
     */
    protected $accessToken = "";
    /**
     * @var string
     */
    protected $tokenType = "";
    /**
     * @var int
     */
    protected $expiresIn = 0;

    /**
     * AccessToken constructor.
     *
     * @param string $accessToken
     * @param string $tokenType
     * @param int    $expiresIn
     */
    final public function __construct(string $accessToken, string $tokenType = "", int $expiresIn = 0)
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
    }

    /**
     * @param array $payload
     *
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

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}
