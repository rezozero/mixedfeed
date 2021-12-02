<?php

namespace RZ\MixedFeed\Env;

use Psr\Cache\CacheItemPoolInterface;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\FacebookPageFeed;
use RZ\MixedFeed\GithubCommitsFeed;
use RZ\MixedFeed\GithubReleasesFeed;
use RZ\MixedFeed\GraphInstagramFeed;
use RZ\MixedFeed\InstagramFeed;
use RZ\MixedFeed\InstagramOEmbedFeed;
use RZ\MixedFeed\MediumFeed;
use RZ\MixedFeed\PinterestBoardFeed;
use RZ\MixedFeed\TwitterFeed;
use RZ\MixedFeed\TwitterSearchFeed;
use RZ\MixedFeed\YoutubePlaylistItemFeed;

class ProviderResolver
{
    /**
     * @return array<\RZ\MixedFeed\FeedProviderInterface>
     *
     * @throws CredentialsException
     */
    public static function parseFromEnvironment(?CacheItemPoolInterface $cache = null): array
    {
        $feedProviders = [];

        if (false !== $facebookPageIds = $_ENV['MF_FACEBOOK_PAGE_ID'] ?? false) {
            $facebookPageIds = \explode(',', $facebookPageIds);

            foreach ($facebookPageIds as $facebookPageId) {
                $facebookProvider = new FacebookPageFeed(
                    $facebookPageId,
                    $_ENV['MF_FACEBOOK_ACCESS_TOKEN'] ?? '',
                    $cache,
                    isset($_ENV['MF_FACEBOOK_FIELDS']) ?
                        \explode(',', $_ENV['MF_FACEBOOK_FIELDS']) :
                        [],
                    $_ENV['MF_FACEBOOK_ENDPOINT'] ?? null
                );
                \array_push($feedProviders, $facebookProvider);
            }
        }

        /*
         * Youtube playlist
         */
        if (false !== $youtubePlaylistIds = \getenv('MF_YOUTUBE_PLAYLIST_ID')) {
            $youtubePlaylistIds = \explode(',', $youtubePlaylistIds);

            foreach ($youtubePlaylistIds as $youtubePlaylistId) {
                $youtubePlaylistProvider = new YoutubePlaylistItemFeed(
                    $youtubePlaylistId,
                    $_ENV['MF_YOUTUBE_API_KEY'] ?? '',
                    $cache
                );
                \array_push($feedProviders, $youtubePlaylistProvider);
            }
        }

        /*
         * Former Instagram API
         */
        if (isset($_ENV['MF_INSTAGRAM_USER_ID'])) {
            $instagramUserIds = \explode(',', $_ENV['MF_INSTAGRAM_USER_ID']);

            foreach ($instagramUserIds as $instagramUserId) {
                $instagramProvider = new InstagramFeed(
                    $instagramUserId,
                    $_ENV['MF_INSTAGRAM_ACCESS_TOKEN'] ?? '',
                    $cache
                );
                \array_push($feedProviders, $instagramProvider);
            }
        }

        /*
         * Graph instagram
         */
        $instagramUserIds = $_ENV['MF_GRAPH_INSTAGRAM_USER_ID'] ?? null;
        $instagramAccessTokens = $_ENV['MF_GRAPH_INSTAGRAM_ACCESS_TOKEN'] ?? null;

        if ($instagramUserIds && $instagramAccessTokens) {
            $instagramUserIds = \explode(',', $instagramUserIds);
            $instagramAccessTokens = \explode(',', $instagramAccessTokens);

            foreach ($instagramUserIds as $i => $instagramUserId) {
                if (!isset($instagramAccessTokens[$i])) {
                    continue;
                }

                $instagramProvider = new GraphInstagramFeed($instagramUserId, $instagramAccessTokens[$i], $cache);
                \array_push($feedProviders, $instagramProvider);
            }
        }

        if (isset($_ENV['MF_INSTAGRAM_OEMBED_ID'])) {
            $instagramOEmbedProvider = new InstagramOEmbedFeed(
                \explode(',', $_ENV['MF_INSTAGRAM_OEMBED_ID']),
                $cache,
            );
            \array_push($feedProviders, $instagramOEmbedProvider);
        }

        if (isset($_ENV['MF_MEDIUM_USERNAME'])) {
            $mediumUserNames = \explode(',', $_ENV['MF_MEDIUM_USERNAME']);

            $mediumUserIds = $_ENV['MF_MEDIUM_USER_ID'];
            $mediumUserIds = \explode(',', $mediumUserIds);

            foreach ($mediumUserNames as $i => $mediumUserName) {
                $mediumUserId = null;

                if (!empty($mediumUserIds[$i])) {
                    $mediumUserId = $mediumUserIds[$i];
                }

                $mediumProvider = new MediumFeed($mediumUserName, $cache, $mediumUserId);
                \array_push($feedProviders, $mediumProvider);
            }
        }

        if (isset($_ENV['MF_PINTEREST_BOARD_ID'])) {
            $pinterestBoardIds = \explode(',', $_ENV['MF_PINTEREST_BOARD_ID']);

            foreach ($pinterestBoardIds as $pinterestBoardId) {
                $pinterestProvider = new PinterestBoardFeed(
                    $pinterestBoardId,
                    $_ENV['MF_PINTEREST_ACCESS_TOKEN'] ?? '',
                    $cache
                );
                \array_push($feedProviders, $pinterestProvider);
            }
        }

        if (isset($_ENV['MF_GITHUB_RELEASES_REPOSITORY'])) {
            $githubReleasesRepos = \explode(',', $_ENV['MF_GITHUB_RELEASES_REPOSITORY']);

            foreach ($githubReleasesRepos as $githubReleasesRepo) {
                $githubReleasesProvider = new GithubReleasesFeed(
                    $githubReleasesRepo,
                    $_ENV['MF_GITHUB_ACCESS_TOKEN'] ?? '',
                    $cache
                );
                \array_push($feedProviders, $githubReleasesProvider);
            }
        }

        if (isset($_ENV['MF_GITHUB_COMMITS_REPOSITORY'])) {
            $githubCommitsRepos = \explode(',', $_ENV['MF_GITHUB_COMMITS_REPOSITORY']);

            foreach ($githubCommitsRepos as $githubCommitsRepo) {
                $githubCommitsProvider = new GithubCommitsFeed(
                    $githubCommitsRepo,
                    $_ENV['MF_GITHUB_ACCESS_TOKEN'] ?? '',
                    $cache
                );
                \array_push($feedProviders, $githubCommitsProvider);
            }
        }

        if (isset($_ENV['MF_TWITTER_USER_ID'])) {
            $twitterUserIds = \explode(',', $_ENV['MF_TWITTER_USER_ID']);

            foreach ($twitterUserIds as $twitterUserId) {
                $twitterProvider = new TwitterFeed(
                    $twitterUserId,
                    $_ENV['MF_TWITTER_CONSUMER_KEY'] ?? '',
                    $_ENV['MF_TWITTER_CONSUMER_SECRET'] ?? '',
                    $_ENV['MF_TWITTER_ACCESS_TOKEN'] ?? '',
                    $_ENV['MF_TWITTER_ACCESS_TOKEN_SECRET'] ?? '',
                    $cache,
                    true,
                    false,
                    (bool) ($_ENV['MF_TWITTER_EXTENDED_MODE'] ?? false)
                );
                \array_push($feedProviders, $twitterProvider);
            }
        }

        if (isset($_ENV['MF_TWITTER_SEARCH_QUERY'])) {
            \parse_str($_ENV['MF_TWITTER_SEARCH_QUERY'], $searchParams);
            $twitterSearchProvider = new TwitterSearchFeed(
                $searchParams,
                $_ENV['MF_TWITTER_CONSUMER_KEY'] ?? '',
                $_ENV['MF_TWITTER_CONSUMER_SECRET'] ?? '',
                $_ENV['MF_TWITTER_ACCESS_TOKEN'] ?? '',
                $_ENV['MF_TWITTER_ACCESS_TOKEN_SECRET'] ?? '',
                $cache,
                (bool) ($_ENV['MF_TWITTER_EXTENDED_MODE'] ?? false)
            );
            \array_push($feedProviders, $twitterSearchProvider);
        }

        return $feedProviders;
    }
}
