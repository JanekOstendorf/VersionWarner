<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\Entities;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\ORM\Mapping as ORM;
use ozzyfant\VersionWarner\CantFetchVersionException;
use ozzyfant\VersionWarner\ITemplateArray;
use ozzyfant\VersionWarner\Notification;
use ozzyfant\VersionWarner\VersionProvider;

/**
 * Class VersionCheck
 * @package ozzyfant\VersionWarner\Entities
 * @ORM\Entity()
 * @ORM\Table(name="checks")
 */
class VersionCheck implements ITemplateArray, ObjectManagerAware
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $enabled;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(name="short_title", type="string")
     * @var string
     */
    private $shortTitle;

    /**
     * @ORM\Column(type="string", name="provider")
     */
    private $providerName;

    /**
     * @ORM\OneToMany(targetEntity="VersionCheckArgument", mappedBy="check")
     */
    private $providerArguments;

    /**
     * @ORM\ManyToMany(targetEntity="Recipient", mappedBy="checks")
     * @var Collection
     */
    private $recipients;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Version", mappedBy="check")
     */
    private $versions;

    /**
     * @var VersionProvider
     */
    private $provider;

    /**
     * @var bool
     */
    private $isNotifying = false;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * VersionCheck constructor.
     */
    public function __construct()
    {
        $this->providerArguments = new ArrayCollection();
        $this->recipients = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getShortTitle(): string
    {
        if (!$this->shortTitle) {
            return $this->getTitle();
        }

        return $this->shortTitle;
    }

    /**
     * @return Collection
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @return VersionProvider
     */
    public function getProvider(): VersionProvider
    {
        return $this->provider;
    }

    public function initProvider()
    {
        // Try to find the VersionProvider
        $providerName = '\\ozzyfant\\VersionWarner\\VersionProviders\\' . $this->providerName;
        $this->provider = new $providerName();

        if ($this->provider instanceof VersionProvider) {
            // Pass the arguments
            $checkArguments = [];
            foreach ($this->getProviderArguments() as $argument) {
                /** @var VersionCheckArgument $argument */
                $checkArguments[$argument->getKey()] = $argument->getValue();
            }

            $this->provider->setArguments($checkArguments);
        } else {
            $this->provider = null;
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getProviderArguments()
    {
        return $this->providerArguments;
    }

    /**
     * @throws CantFetchVersionException
     */
    public function runCheck()
    {
        if (!is_null($this->provider)) {

            $latestVersion = new Version($this->provider->getLatestVersion(), $this);

            // get the last checked version
            if ($this->versions->count() > 0) {

                /** @var Version $lastVersion */
                $lastVersion = $this->versions->last();

                // If we do not have a new version, update the existing version with the new "last_seen" time
                if (!$this->provider->isNewVersion($lastVersion, $latestVersion)) {
                    $lastVersion->setLastChecked();
                    $this->em->persist($lastVersion);
                } else {
                    // we've detected a new version!
                    $this->versions->add($latestVersion);
                    $this->em->persist($latestVersion);
                    $this->isNotifying = true;
                    $this->notification = new Notification($lastVersion, $latestVersion, $this->provider->getDownloadLink(), $this);
                }
            } else {
                // No previous records. Save a new version
                $this->versions->add($latestVersion);
                $this->em->persist($latestVersion);

            }
        }

    }

    /**
     * Returns an array to use with template engines
     * @return array
     */
    public function toTemplateArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title
        ];
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Collection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }


    /**
     * Injects responsible ObjectManager and the ClassMetadata into this persistent object.
     * @param ObjectManager $objectManager
     * @param ClassMetadata $classMetadata
     * @return void
     */
    public function injectObjectManager(ObjectManager $objectManager, ClassMetadata $classMetadata)
    {
        $this->em = $objectManager;
    }

    /**
     * Is it possible to run this check again? Checks against the minimal check interval
     * @see VersionProvider::getMinimalCheckInterval()
     * @return bool Is it possible to run this check again?
     */
    public function checkRunInterval()
    {
        if ($this->versions->isEmpty()) {
            return true;
        }

        $lastTimeRun = $this->versions->last()->getLastChecked()->getTimestamp();
        $now = (new \DateTime())->getTimestamp();
        return ((int)($now - $lastTimeRun) >= $this->provider->getMinimalCheckInterval());
    }

    /**
     * @return bool
     */
    public function isNotifying(): bool
    {
        return $this->isNotifying;
    }

    /**
     * @return Notification
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }
}