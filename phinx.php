<?php
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

define('DIR_ROOT', __DIR__);

require __DIR__ . '/src/VersionWarner.php';
$app = new \ozzyfant\VersionWarner\VersionWarner();

$config = require __DIR__ . '/config.php';

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds' => __DIR__ . '/db/seeds'
    ],
    'environments' => [
        'default_database' => 'default',
        'default' => [
            'name' => $config['database']['database'],
            'connection' => $app->getDb()->getWrappedConnection()
        ]
    ]
];