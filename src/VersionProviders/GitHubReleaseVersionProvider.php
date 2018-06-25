<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\VersionProviders;


class GitHubReleaseVersionProvider extends FeedVersionProvider
{

    const FEED_SUFFIX = '/releases.atom';

    function setArguments(array $arguments)
    {
        if (isset($arguments['repositoryUrl'])) {
            $this->feedUrl = $arguments['repositoryUrl'] . self::FEED_SUFFIX;
        }
    }

}