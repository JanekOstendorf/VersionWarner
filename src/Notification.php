<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner;


use ozzyfant\VersionWarner\Entities\Version;
use ozzyfant\VersionWarner\Entities\VersionCheck;

class Notification implements ITemplateArray
{
    private $newVersionDownload;
    private $check;
    private $oldVersion;
    private $newVersion;

    /**
     * Notification constructor.
     * @param Version $versionOld
     * @param Version $versionNew
     * @param string $download Download for the new version
     * @param VersionCheck $check
     */
    public function __construct(Version $versionOld, Version $versionNew, string $download, VersionCheck $check)
    {
        $this->check = $check;
        $this->oldVersion = $versionOld;
        $this->newVersion = $versionNew;
        $this->newVersionDownload = $download;
    }

    public function toTemplateArray(): array
    {
        return [
            'check' => $this->check->toTemplateArray(),
            'oldVersion' => $this->oldVersion->toTemplateArray(),
            'newVersion' => $this->newVersion->toTemplateArray(),
            'newVersionDownload' => $this->newVersionDownload
        ];
    }

    /**
     * @return VersionCheck
     */
    public function getCheck(): VersionCheck
    {
        return $this->check;
    }

    /**
     * @return Version
     */
    public function getOldVersion(): Version
    {
        return $this->oldVersion;
    }

    /**
     * @return Version
     */
    public function getNewVersion(): Version
    {
        return $this->newVersion;
    }

    /**
     * @return string
     */
    public function getNewVersionDownload()
    {
        return $this->newVersionDownload;
    }

}