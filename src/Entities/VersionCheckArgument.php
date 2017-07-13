<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class VersionCheckArgument
 * @package ozzyfant\VersionWarner\Entities
 *
 * @ORM\Entity()
 * @ORM\Table(name="checks_arguments")
 */
class VersionCheckArgument
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="VersionCheck", inversedBy="providerArguments")
     * @ORM\JoinColumn(name="check_id", referencedColumnName="id")
     * @var VersionCheck
     */
    private $check;

    /**
     * @ORM\Column(type="string")
     */
    private $key;

    /**
     * @ORM\Column(type="string")
     */
    private $value;

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return VersionCheck
     */
    public function getCheck(): VersionCheck
    {
        return $this->check;
    }
}