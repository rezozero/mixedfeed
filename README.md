# mixedfeed
A PHP library to get social networks feeds and merge them

![License](http://img.shields.io/:license-mit-blue.svg)

## Install

```shell
composer require rezozero/mixedfeed
```

```php
use Rezo-Zero\MixedFeed\MixedFeed;
use Rezo-Zero\MixedFeed\InstagramFeed;
use Rezo-Zero\MixedFeed\TwitterFeed;
use Rezo-Zero\MixedFeed\TwitterSearchFeed;
use Rezo-Zero\MixedFeed\FacebookPageFeed;

$feed = new MixedFeed([
    new InstagramFeed(
        ‘instagram_user_id’,
        ‘instagram_access_token’
        // you can add a doctrine cache provider
    ),
    new TwitterFeed(
        ‘twitter_user_id’,
        ‘twitter_consumer_key’,
        ‘twitter_consumer_secret’,
        ‘twitter_access_token’,
        ‘twitter_access_token_secret’
        // you can add a doctrine cache provider
    ),
    new TwitterSearchFeed(
        [
            'from' => 'rezo_zero',
            'since' => '2015-11-01',
            'until' => '2015-11-30'
        ],
        ‘twitter_consumer_key’,
        ‘twitter_consumer_secret’,
        ‘twitter_access_token’,
        ‘twitter_access_token_secret’
        // you can add a doctrine cache provider
    ),
    new FacebookPageFeed(
        ‘page-id’,
        ‘app_access_token’
        // you can add a doctrine cache provider
        // And a fields array to retrieve too
    ),
]);

return $feed->getItems(12);
```

## Combine feeds

*mixedfeed* can combine multiple social feeds so you can loop over them and use some common data fields such as `feedItemPlatform` and `normalizedDate`.

Each feed provider must inject these two parameters in feed items:

* `feedItemPlatform`: This is your social network name as a *string* i.e. «twitter». It will be important to cache your feed and for your HTML template engine to render properly each feed item.

For example, if you are using *Twig*, you will be able to include a sub-template for each social-platform.

```twig
{% for socialItem in mixedFeedItems %}
{% include ‘social-blocks/‘ ~ socialItem.feedItemPlatform ~ ‘.html.twig’ %}
{% endfor %}
```

* `normalizedDate`: This is a crucial parameter as it allows *mixedfeed* library to sort *antechronologically* multiple feeds with heterogeneous structures.

## Feed providers

|  Feed provider class  |  Description |
| -------------- | ---------------- |
| InstagramFeed | Call over `/v1/users/$userId/media/recent/` endpoint. It needs a `$userId` and an `$accessToken` |
| TwitterFeed | Call over `statuses/user_timeline` endpoint. It requires a `$userId`, a `$consumerKey`, a `$consumerSecret`, an `$accessToken` and an `$accessTokenSecret`. Be careful, Twitter API won’t retrieve tweets older than 4-5 month, your item count could be lesser than expected. In the same way, Twitter removes retweets after retrieving the items count. |
| TwitterSearchFeed | Call over `search/tweets` endpoint. It requires a `$queryParams` array, a `$consumerKey`, a `$consumerSecret`, an `$accessToken` and an `$accessTokenSecret`. Be careful, Twitter API won’t retrieve tweets older than 4-5 month, your item count could be lesser than expected. `$queryParams` must be a *key-valued* array with *query operators* according to [Twitter API documentation](https://dev.twitter.com/rest/public/search).  |
| FacebookPageFeed | Call over `https://graph.facebook.com/$pageId/posts` endpoint. It requires a `$pageId` and an `$accessToken`. This feed provider only works for public Facebook **pages**. To get an access-token visit: https://developers.facebook.com/docs/facebook-login/access-tokens. By default, `link`, `picture`, `message`, `story`, `type`, `created_time`, `source`, `status_type` fields are queried, you can add your own by passing `$field` array as last parameter. You can add `since` and `until` query parameters using `setSince(\Datetime)` and `setUntil(\Datetime)` methods. |
| PinterestBoardFeed | Call over `/v1/boards/$boardId/pins/` endpoint. It requires a `$boardId` and an `$accessToken`. To get an access-token visit: https://developers.pinterest.com/tools/access_token/ |
