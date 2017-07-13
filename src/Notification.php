<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner;


class Notification implements ITemplateArray
{
    private $check;
    private $oldVersion;
    private $newVersion;

    /**
     * Notification constructor.
     * @param Version $versionOld
     * @param Version $versionNew
     * @param VersionCheck $check
     */
    public function __construct(Version $versionOld, Version $versionNew, VersionCheck $check)
    {
        $this->check = $check;
        $this->oldVersion = $versionOld;
        $this->newVersion = $versionNew;
    }

    public function toTemplateArray()
    {
        return [
            'check' => $this->check->toTemplateArray(),
            'oldVersion' => $this->oldVersion->toTemplateArray(),
            'newVersion' => $this->newVersion->toTemplateArray()
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

}