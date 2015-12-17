# mixedfeed
A PHP library to get social networks feeds and merge them

![License](http://img.shields.io/:license-mit-blue.svg)

## Install

```shell
composer require rezozero/mixedfeed
```

```php
use RZ\MixedFeed\MixedFeed;
use RZ\MixedFeed\InstagramFeed;
use RZ\MixedFeed\TwitterFeed;
use RZ\MixedFeed\TwitterSearchFeed;
use RZ\MixedFeed\FacebookPageFeed;
use RZ\MixedFeed\GithubReleasesFeed;
use RZ\MixedFeed\GithubCommitsFeed;

$feed = new MixedFeed([
    new InstagramFeed(
        'instagram_user_id',
        'instagram_access_token',
        // you can add a doctrine cache provider
    ),
    new TwitterFeed(
        'twitter_user_id',
        'twitter_consumer_key',
        'twitter_consumer_secret',
        'twitter_access_token',
        'twitter_access_token_secret',
        // you can add a doctrine cache provider
    ),
    new TwitterSearchFeed(
        [
            '#art', // do not specify a key for string searchs
            'from' => 'rezo_zero',
            'since' => '2015-11-01',
            'until' => '2015-11-30',
        ],
        'twitter_consumer_key',
        'twitter_consumer_secret',
        'twitter_access_token',
        'twitter_access_token_secret',
        // you can add a doctrine cache provider
    ),
    new FacebookPageFeed(
        'page-id',
        'app_access_token',
        // you can add a doctrine cache provider
        // And a fields array to retrieve too
    ),
    new GithubCommitsFeed(
        'symfony/symfony',
        'access_token',
        // you can add a doctrine cache provider
    ),
    new GithubReleasesFeed(
        'roadiz/roadiz',
        'access_token',
        // you can add a doctrine cache provider
    ),
]);

return $feed->getItems(12);
```

## Combine feeds

*mixedfeed* can combine multiple social feeds so you can loop over them and use some common data fields such as `feedItemPlatform`, `normalizedDate` and `canonicalMessage`.

Each feed provider must inject these three parameters in feed items:

* `feedItemPlatform`: This is your social network name as a *string* i.e. «twitter». It will be important to cache your feed and for your HTML template engine to render properly each feed item.

For example, if you are using *Twig*, you will be able to include a sub-template for each social-platform.

```twig
{% for socialItem in mixedFeedItems %}
{% include ‘social-blocks/‘ ~ socialItem.feedItemPlatform ~ ‘.html.twig’ %}
{% endfor %}
```

* `normalizedDate`: This is a crucial parameter as it allows *mixedfeed* library to sort *antechronologically* multiple feeds with heterogeneous structures.
* `canonicalMessage`: This is a useful field which contains the **text content** for each item over **all** platforms. You can use this to display items texts within a simple loop.

## Feed providers

|  Feed provider class  |  Description | `feedItemPlatform` |
| -------------- | ---------------- | ------------------ |
| InstagramFeed | Call over `/v1/users/$userId/media/recent/` endpoint. It needs a `$userId` and an `$accessToken` | `instagram` |
| TwitterFeed | Call over `statuses/user_timeline` endpoint. It requires a `$userId`, a `$consumerKey`, a `$consumerSecret`, an `$accessToken` and an `$accessTokenSecret`. Be careful, Twitter API won’t retrieve tweets older than 4-5 month, your item count could be lesser than expected. In the same way, Twitter removes retweets after retrieving the items count. | `twitter` |
| TwitterSearchFeed | Call over `search/tweets` endpoint. It requires a `$queryParams` array, a `$consumerKey`, a `$consumerSecret`, an `$accessToken` and an `$accessTokenSecret`. Be careful, Twitter API won’t retrieve tweets older than 4-5 month, your item count could be lesser than expected. `$queryParams` must be a *key-valued* array with *query operators* according to [Twitter API documentation](https://dev.twitter.com/rest/public/search). | `twitter` |
| FacebookPageFeed | Call over `https://graph.facebook.com/$pageId/posts` endpoint. It requires a `$pageId` and an `$accessToken`. This feed provider only works for public Facebook **pages**. To get an access-token visit: https://developers.facebook.com/docs/facebook-login/access-tokens. By default, `link`, `picture`, `message`, `story`, `type`, `created_time`, `source`, `status_type` fields are queried, you can add your own by passing `$field` array as last parameter. You can add `since` and `until` query parameters using `setSince(\Datetime)` and `setUntil(\Datetime)` methods. | `facebook_page` |
| PinterestBoardFeed | Call over `/v1/boards/$boardId/pins/` endpoint. It requires a `$boardId` and an `$accessToken`. To get an access-token visit: https://developers.pinterest.com/tools/access_token/ | `pinterest_board` |
| GithubReleasesFeed | Call over `api.github.com/repos/:user/:repo/releases` endpoint. It requires a `$repository` (*user/repository*) and an `$accessToken`. You can add a last `$page` parameter. To get an access-token visit: https://github.com/settings/tokens | `github_release` |
| GithubCommitsFeed | Call over `api.github.com/repos/:user/:repo/commits` endpoint. It requires a `$repository` (*user/repository*) and an `$accessToken`. You can add a last `$page` parameter. To get an access-token visit: https://github.com/settings/tokens | `github_commit` |

## Modify cache TTL

Each feed-provider which inherits from `AbstractFeedProvider` has access to `setTtl()` method in order to modify the default cache time.
By default it is set for `7200` seconds, so you can adjust it to invalidate doctrine cache more or less often.

## Create your own feed provider

There are plenty of APIs on the internet, and this tool won’t be able to handle them all.
No problem, you can easily create your own feed provider to use in *mixedfeed*. You just have to create a new *class* that
will inherit from `RZ\MixedFeed\AbstractFeedProvider`. Then you will have to implement each method from `FeedProviderInterface`:

* `getDateTime` method to look for the critical datetime field in your feed.
* `getFeed` method to consume your API endpoint with a count limit and take care of caching your responses.
* `getCanonicalMessage` method to look for the important text content in your feed.
* `getFeedPlatform` method to get a global text identifier for your feed items.
* `isValid` method to check if API call has succeeded regarding feed content.
* `getErrors` method to errors from API feed that did not succeed.
* then a *constructor* that will be handy to use directly in the MixedFeed initialization.

Feel free to check our existing Feed providers to see how they work. And we strongly advise you to
implement a caching system not to call your API endpoints at each request. By default, we use *Doctrine*’s caching
system which has many storage options.
