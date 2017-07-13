<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner;


interface ITemplateArray
{
    /**
     * Returns an array to use with template engines
     * @return array
     */
    public function toTemplateArray(): array;
}