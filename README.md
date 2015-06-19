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

$feed = new MixedFeed([
    new InstagramFeed(
        'instagram_user_id',
        'instagram_access_token'
        // you can add a doctrine cache provider
    ),
    new TwitterFeed(
        'twitter_user_id',
        'twitter_consumer_key',
        'twitter_consumer_secret',
        'twitter_access_token',
        'twitter_access_token_secret'
        // you can add a doctrine cache provider
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
{% include 'social-blocks/' ~ socialItem.feedItemPlatform ~ '.html.twig' %}
{% endfor %}
```

* `normalizedDate`: This is a crucial parameter as it allows *mixedfeed* library to sort *antechronologically* multiple feeds with heterogeneous structures.
