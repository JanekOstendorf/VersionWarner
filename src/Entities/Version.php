<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\Entities;


use Doctrine\ORM\Mapping as ORM;
use ozzyfant\VersionWarner\ITemplateArray;

/**
 * Class Version
 * @package ozzyfant\VersionWarner\Entities
 *
 * @ORM\Entity()
 * @ORM\Table(name="versions")
 */
class Version implements ITemplateArray
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * The version string
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $version;

    /**
     * The time this version has been first seen
     * @var \DateTime
     *
     * @ORM\Column(name="first_seen", type="datetime")
     */
    private $firstSeen;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_checked", type="datetime")
     */
    private $lastChecked;

    /**
     * @var VersionCheck
     * @ORM\ManyToOne(targetEntity="VersionCheck", inversedBy="versions")
     * @ORM\JoinColumn(name="check_id", referencedColumnName="id")
     */
    private $check;

    /**
     * Version constructor.
     * @param $version
     * @param VersionCheck $check
     * @param \DateTime $firstSeen   Default: now
     * @param \DateTime $lastChecked Default: now
     */
    public function __construct($version, VersionCheck $check, \DateTime $firstSeen = null, \DateTime $lastChecked = null)
    {
        $this->version = $version;
        $this->firstSeen = ($firstSeen === null ? new \DateTime() : $firstSeen);
        $this->lastChecked = ($lastChecked === null ? new \DateTime() : $lastChecked);
        $this->check = $check;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param \DateTime $firstSeen
     * @return Version
     */
    public function setFirstSeen(\DateTime $firstSeen = null): Version
    {
        $this->firstSeen = ($firstSeen === null ? new \DateTime() : $firstSeen);
        return $this;
    }


    /**
     * Returns an array to use with template engines
     * @return array
     */
    public function toTemplateArray(): array
    {
        return [
            'version' => $this->version,
            'firstSeen' => $this->firstSeen,
            'lastChecked' => $this->lastChecked
        ];
    }

    /**
     * @param \DateTime $lastChecked
     * @return Version
     */
    public function setLastChecked(\DateTime $lastChecked = null): Version
    {
        $this->lastChecked = ($lastChecked === null ? new \DateTime() : $lastChecked);
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFirstSeen(): \DateTime
    {
        return $this->firstSeen;
    }

    /**
     * @return \DateTime
     */
    public function getLastChecked(): \DateTime
    {
        return $this->lastChecked;
    }

    /**
     * @return VersionCheck
     */
    public function getCheck(): VersionCheck
    {
        return $this->check;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getVersion();
    }
}