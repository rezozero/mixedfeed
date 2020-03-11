<?php
namespace RZ\MixedFeed\Env;

use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\FacebookPageFeed;
use RZ\MixedFeed\FeedProviderInterface;
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
     * @param CacheProvider|null $cache
     *
     * @return FeedProviderInterface[]
     * @throws \RZ\MixedFeed\Exception\CredentialsException
     */
    public static function parseFromEnvironment(CacheProvider $cache = null)
    {
        $feedProviders = [];
        if (false !== $facebookPageIds = getenv('MF_FACEBOOK_PAGE_ID')) {
            $facebookPageIds = explode(',', $facebookPageIds);
            foreach ($facebookPageIds as $facebookPageId) {
                $facebookProvider = new FacebookPageFeed(
                    $facebookPageId,
                    getenv('MF_FACEBOOK_ACCESS_TOKEN'),
                    $cache,
                    getenv('MF_FACEBOOK_FIELDS') ?
                        explode(',', getenv('MF_FACEBOOK_FIELDS')):
                        [],
                    getenv('MF_FACEBOOK_ENDPOINT')
                );
                array_push($feedProviders, $facebookProvider);
            }
        }
        /*
         * Youtube playlist
         */
        if (false !== $youtubePlaylistIds = getenv('MF_YOUTUBE_PLAYLIST_ID')) {
            $youtubePlaylistIds = explode(',', $youtubePlaylistIds);
            foreach ($youtubePlaylistIds as $youtubePlaylistId) {
                $youtubePlaylistProvider = new YoutubePlaylistItemFeed(
                    $youtubePlaylistId,
                    getenv('MF_YOUTUBE_API_KEY'),
                    $cache
                );
                array_push($feedProviders, $youtubePlaylistProvider);
            }
        }
        /*
         * Former Instagram API
         */
        if (false !== $instagramUserIds = getenv('MF_INSTAGRAM_USER_ID')) {
            $instagramUserIds = explode(',', $instagramUserIds);
            foreach ($instagramUserIds as $instagramUserId) {
                $instagramProvider = new InstagramFeed(
                    $instagramUserId,
                    getenv('MF_INSTAGRAM_ACCESS_TOKEN'),
                    $cache
                );
                array_push($feedProviders, $instagramProvider);
            }
        }
        /*
         * Graph instagram
         */
        $instagramUserIds = getenv('MF_GRAPH_INSTAGRAM_USER_ID');
        $instagramAccessTokens = getenv('MF_GRAPH_INSTAGRAM_ACCESS_TOKEN');
        if (false !== $instagramUserIds && false !== $instagramAccessTokens) {
            $instagramUserIds = explode(',', $instagramUserIds);
            $instagramAccessTokens = explode(',', $instagramAccessTokens);
            foreach ($instagramUserIds as $i => $instagramUserId) {
                if (isset($instagramAccessTokens[$i])) {
                    $instagramProvider = new GraphInstagramFeed(
                        $instagramUserId,
                        $instagramAccessTokens[$i],
                        $cache
                    );
                    array_push($feedProviders, $instagramProvider);
                }
            }
        }
        if (false !== $instagramOEmbedId = getenv('MF_INSTAGRAM_OEMBED_ID')) {
            $instagramOEmbedProvider = new InstagramOEmbedFeed(
                explode(',', $instagramOEmbedId),
                $cache
            );
            array_push($feedProviders, $instagramOEmbedProvider);
        }
        if (false !== $mediumUserNames = getenv('MF_MEDIUM_USERNAME')) {
            $mediumUserNames = explode(',', $mediumUserNames);
            if (false !== $mediumUserIds = getenv('MF_MEDIUM_USER_ID')) {
                $mediumUserIds = explode(',', $mediumUserIds);
            } else {
                $mediumUserIds = [];
            }

            foreach ($mediumUserNames as $i => $mediumUserName) {
                $mediumUserId = null;
                if (!empty($mediumUserIds[$i])) {
                    $mediumUserId = $mediumUserIds[$i];
                }
                $mediumProvider = new MediumFeed(
                    $mediumUserName,
                    $cache,
                    $mediumUserId
                );
                array_push($feedProviders, $mediumProvider);
            }
        }
        if (false !== $pinterestBoardIds = getenv('MF_PINTEREST_BOARD_ID')) {
            $pinterestBoardIds = explode(',', $pinterestBoardIds);
            foreach ($pinterestBoardIds as $pinterestBoardId) {
                $pinterestProvider = new PinterestBoardFeed(
                    $pinterestBoardId,
                    getenv('MF_PINTEREST_ACCESS_TOKEN'),
                    $cache
                );
                array_push($feedProviders, $pinterestProvider);
            }
        }
        if (false !== $githubReleasesRepos = getenv('MF_GITHUB_RELEASES_REPOSITORY')) {
            $githubReleasesRepos = explode(',', $githubReleasesRepos);
            foreach ($githubReleasesRepos as $githubReleasesRepo) {
                $githubReleasesProvider = new GithubReleasesFeed(
                    $githubReleasesRepo,
                    getenv('MF_GITHUB_ACCESS_TOKEN'),
                    $cache
                );
                array_push($feedProviders, $githubReleasesProvider);
            }
        }
        if (false !== $githubCommitsRepos = getenv('MF_GITHUB_COMMITS_REPOSITORY')) {
            $githubCommitsRepos = explode(',', $githubCommitsRepos);
            foreach ($githubCommitsRepos as $githubCommitsRepo) {
                $githubCommitsProvider = new GithubCommitsFeed(
                    $githubCommitsRepo,
                    getenv('MF_GITHUB_ACCESS_TOKEN'),
                    $cache
                );
                array_push($feedProviders, $githubCommitsProvider);
            }
        }
        if (false !== $twitterUserIds = getenv('MF_TWITTER_USER_ID')) {
            $twitterUserIds = explode(',', $twitterUserIds);
            foreach ($twitterUserIds as $twitterUserId) {
                $twitterProvider = new TwitterFeed(
                    $twitterUserId,
                    getenv('MF_TWITTER_CONSUMER_KEY'),
                    getenv('MF_TWITTER_CONSUMER_SECRET'),
                    getenv('MF_TWITTER_ACCESS_TOKEN'),
                    getenv('MF_TWITTER_ACCESS_TOKEN_SECRET'),
                    $cache,
                    true,
                    false,
                    (bool) getenv('MF_TWITTER_EXTENDED_MODE')
                );
                array_push($feedProviders, $twitterProvider);
            }
        }
        if (false !== $twitterSearch = getenv('MF_TWITTER_SEARCH_QUERY')) {
            parse_str($twitterSearch, $searchParams);
            $twitterSearchProvider = new TwitterSearchFeed(
                $searchParams,
                getenv('MF_TWITTER_CONSUMER_KEY'),
                getenv('MF_TWITTER_CONSUMER_SECRET'),
                getenv('MF_TWITTER_ACCESS_TOKEN'),
                getenv('MF_TWITTER_ACCESS_TOKEN_SECRET'),
                $cache,
                (bool) getenv('MF_TWITTER_EXTENDED_MODE')
            );
            array_push($feedProviders, $twitterSearchProvider);
        }

        return $feedProviders;
    }
}
