<?php
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\VersionProviders;


use ozzyfant\VersionWarner\VersionProvider;

class TeamSpeakVersionProvider extends VersionProvider
{
    const VERSION_URL = 'https://www.teamspeak.com/versions/server.json';

    const ARCHITECTURES = [
        'x86',
        'x86_64'
    ];

    const OPERATING_SYSTEMS = [
        'windows',
        'macos',
        'linux',
        'freebsd'
    ];

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
     * Operating system we're getting info for
     * Possible: windows, macos, linux, freebsd
     * @var string
     */
    protected $os = 'linux';

    /**
     * Architecture we're getting info for
     * Possible: x86, x86_64
     * @var string
     */
    protected $architecture = 'x86_64';

    /**
     * Is this a valid operating system?
     * @param string $os
     * @return bool
     */
    public static function isValidOperatingSystem(string $os): bool
    {
        return in_array($os, self::OPERATING_SYSTEMS);
    }

    /**
     * Get the (download) link for the new version.
     * @return string
     */
    function getDownloadLink(): string
    {
        if (isset(self::$latestDownloadLinkCache[$this->os][$this->architecture])) {
            return self::$latestDownloadLinkCache[$this->os][$this->architecture];
        }

        if (!self::$responsePresent) {
            self::$lastResponse = json_decode(file_get_contents(self::VERSION_URL), true);
            self::$responsePresent = true;
        }

        self::$latestDownloadLinkCache[$this->os][$this->architecture] = reset(self::$lastResponse[$this->os][$this->architecture]['mirrors']);
        return self::$latestDownloadLinkCache[$this->os][$this->architecture];
    }

    /**
     * What is the latest version?
     * @return string
     */
    function getLatestVersion(): string
    {
        if (isset(self::$latestVersionCache[$this->os][$this->architecture])) {
            return self::$latestVersionCache[$this->os][$this->architecture];
        }

        if (!self::$responsePresent) {
            self::$lastResponse = json_decode(file_get_contents(self::VERSION_URL), true);
            self::$responsePresent = true;
        }

        self::$latestVersionCache[$this->os][$this->architecture] = self::$lastResponse[$this->os][$this->architecture]['version'];
        return self::$latestVersionCache[$this->os][$this->architecture];
    }

    /**
     * Set the arguments provided in the config file
     * @param array $arguments The arguments from the config file
     */
    function setArguments(array $arguments): void
    {
        // Set architecture and operating system
        if (isset($arguments['architecture']) && self::isValidArchitecture($arguments['architecture'])) {
            $this->architecture = $arguments['architecture'];
        }

        if (isset($arguments['os']) && self::isValidArchitecture($arguments['os'])) {
            $this->os = $arguments['os'];
        }
    }

    /**
     * Is this architecture valid?
     * @param string $architecture
     * @return bool
     */
    public static function isValidArchitecture(string $architecture): bool
    {
        return in_array($architecture, self::ARCHITECTURES);
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