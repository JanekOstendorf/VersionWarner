<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

define('DIR_ROOT', dirname(__DIR__));
require_once DIR_ROOT . '/vendor/autoload.php';

$app = new \ozzyfant\VersionWarner\VersionWarner();
$app->runConsole();