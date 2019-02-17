<?php
namespace RZ\MixedFeed\Env;

use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\FacebookPageFeed;
use RZ\MixedFeed\FeedProviderInterface;
use RZ\MixedFeed\GithubCommitsFeed;
use RZ\MixedFeed\GithubReleasesFeed;
use RZ\MixedFeed\InstagramFeed;
use RZ\MixedFeed\InstagramOEmbedFeed;
use RZ\MixedFeed\MediumFeed;
use RZ\MixedFeed\PinterestBoardFeed;

class ProviderResolver
{
    /**
     * @param CacheProvider|null $cache
     *
     * @return FeedProviderInterface[]
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
                    $cache
                );
                array_push($feedProviders, $facebookProvider);
            }
        }
        if (false !== $instagramUserId = getenv('MF_INSTAGRAM_USER_ID')) {
            $instagramProvider = new InstagramFeed(
                $instagramUserId,
                getenv('MF_INSTAGRAM_ACCESS_TOKEN'),
                $cache
            );
            array_push($feedProviders, $instagramProvider);
        }
        if (false !== $instagramOEmbedId = getenv('MF_INSTAGRAM_OEMBED_ID')) {
            $instagramOEmbedProvider = new InstagramOEmbedFeed(
                explode(',', $instagramOEmbedId),
                $cache
            );
            array_push($feedProviders, $instagramOEmbedProvider);
        }
        if (false !== $mediumUserIds = getenv('MF_MEDIUM_USER_ID')) {
            $mediumUserIds = explode(',', $mediumUserIds);
            foreach ($mediumUserIds as $mediumUserId) {
                $mediumProvider = new MediumFeed(
                    $mediumUserId,
                    $cache
                );
                array_push($feedProviders, $mediumProvider);
            }
        }
        if (false !== $pinterestBoardId = getenv('MF_PINTEREST_BOARD_ID')) {
            $pinterestProvider = new PinterestBoardFeed(
                $pinterestBoardId,
                getenv('MF_PINTEREST_ACCESS_TOKEN'),
                $cache
            );
            array_push($feedProviders, $pinterestProvider);
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

        return $feedProviders;
    }
}
