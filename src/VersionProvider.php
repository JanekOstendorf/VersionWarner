<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner;

use ozzyfant\VersionWarner\Entities\Version;

abstract class VersionProvider
{

    /**
     * What is the latest version?
     * @return string
     */
    abstract function getLatestVersion(): string;

    /**
     * Get the (download) link for the new version.
     * @return string
     */
    abstract function getDownloadLink(): string;

    /**
     * Set the arguments provided in the config file
     * @param array $arguments The arguments from the config file
     */
    abstract function setArguments(array $arguments): void;

    /**
     * How often are we allowed to ask this provider?
     * @return int Seconds
     */
    abstract function getMinimalCheckInterval(): int;

    /**
     * Check if we need to notify for the new version
     * @param Version $oldVersion The last version we received
     * @param Version $newVersion The new version we received
     * @return bool Is it necessary to notify about this version change?
     */
    function isNewVersion(Version $oldVersion, Version $newVersion): bool
    {
        return ($oldVersion->getVersion() != $newVersion->getVersion()) &&
            ($oldVersion->getLastChecked() < $newVersion->getLastChecked());
    }
}