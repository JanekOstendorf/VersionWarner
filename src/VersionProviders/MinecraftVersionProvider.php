<?php
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\VersionProviders;


use ozzyfant\VersionWarner\VersionProvider;

class MinecraftVersionProvider extends VersionProvider
{
    const VERSION_URL = 'https://launchermeta.mojang.com/mc/game/version_manifest.json';

    /**
     * Save the last response from the API. We don't have to run again if we already asked for info at another point
     * in runtime.
     * @var array Array decoded from JSON response
     */
    protected static $lastResponse;

    /**
     * Did we already ask the API and have a response saved?
     * @var bool
     */
    protected static $responsePresent = false;

    /**
     * @var array
     */
    protected static $latestVersionCache;

    /**
     * @var array
     */
    protected static $latestDownloadLinkCache;

    /**
     * The minecraft version (release or snapshot) we're asking for
     * @var string
     */
    protected $type = 'release';

    /**
     * Get the (download) link for the new version.
     * @return string
     */
    function getDownloadLink(): string
    {
        if (isset(self::$latestDownloadLinkCache[$this->type])) {
            return self::$latestDownloadLinkCache[$this->type];
        }

        if (!self::$responsePresent) {
            self::$lastResponse = json_decode(file_get_contents(self::VERSION_URL), true);
            self::$responsePresent = true;
        }

        $allVersions = self::$lastResponse;
        $latestInfo = $allVersions['versions'][array_search($this->getLatestVersion(),
            array_column($allVersions['versions'], 'id'))];

        // Parse the info json-file
        $info = json_decode(file_get_contents($latestInfo['url']), true);

        self::$latestDownloadLinkCache[$this->type] = $info['downloads']['server']['url'];
        return self::$latestDownloadLinkCache[$this->type];
    }

    /**
     * What is the latest version?
     * @return string
     */
    function getLatestVersion(): string
    {
        if (isset(self::$latestVersionCache[$this->type])) {
            return self::$latestVersionCache[$this->type];
        }

        if (!self::$responsePresent) {
            self::$lastResponse = json_decode(file_get_contents(self::VERSION_URL), true);
            self::$responsePresent = true;
        }

        $allVersions = self::$lastResponse;
        $latestVersions = $allVersions['latest'];

        self::$latestVersionCache[$this->type] = $latestVersions[$this->type];
        return self::$latestVersionCache[$this->type];
    }

    /**
     * Set the arguments provided in the config file
     * @param array $arguments The arguments from the config file
     */
    function setArguments(array $arguments)
    {
        // Only alternative is snapshot. Default is release
        if (isset($arguments['type']) && $arguments['type'] == 'snapshot') {
            $this->type = 'snapshot';
        }
    }

    /**
     * How often are we allowed to ask this provider?
     * @return int Seconds
     */
    function getMinimalCheckInterval(): int
    {
        return 120;
    }

}