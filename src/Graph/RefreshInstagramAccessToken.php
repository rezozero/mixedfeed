<?php

namespace RZ\MixedFeed\Graph;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils as GuzzleUtils;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

final class RefreshInstagramAccessToken
{
    private string $accessToken;

    private static string $grantType = 'ig_refresh_token';

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /** @throws FeedProviderErrorException */
    public function getRefreshedAccessToken(): AccessToken
    {
        $value = \http_build_query([
            'grant_type'   => self::$grantType,
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

            $body = GuzzleUtils::jsonDecode($response->getBody()->getContents(), true);

            return AccessToken::fromArray((array) $body);
        } catch (GuzzleException $e) {
            throw new FeedProviderErrorException(RefreshInstagramAccessToken::class, $e->getMessage(), $e);
        }
    }
}
