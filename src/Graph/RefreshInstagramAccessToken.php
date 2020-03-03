<?php
declare(strict_types=1);

namespace RZ\MixedFeed\Graph;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

final class RefreshInstagramAccessToken
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private static $grantType = 'ig_refresh_token';

    /**
     * RefreshInstagramAccessToken constructor.
     *
     * @param string $accessToken
     */
    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @throws FeedProviderErrorException
     */
    public function getRefreshedAccessToken(): AccessToken
    {
        $value = \http_build_query([
            'grant_type' => static::$grantType,
            'access_token' => $this->accessToken,
        ]);

        try {
            $client = new Client([
                'http_errors' => true,
            ]);
            $response = $client->send(new Request(
                'GET',
                'https://graph.instagram.com/refresh_access_token?'.$value
            ));
            return AccessToken::fromArray(json_decode($response->getBody()->getContents(), true));
        } catch (GuzzleException $e) {
            throw new FeedProviderErrorException(RefreshInstagramAccessToken::class, $e->getMessage(), $e);
        }
    }
}
