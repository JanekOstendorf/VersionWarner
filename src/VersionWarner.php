<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner;


use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Knp\Provider\ConsoleServiceProvider;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ozzyfant\VersionWarner\Actions\RunCommand;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;

class VersionWarner
{
    const VERSION = '0.1-dev';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Recipient[]
     */
    protected $recipients = [];

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Bootstraps the app
     */
    public function __construct()
    {

        $this->app = new Application();

        // Initialize logger
        $this->app->register(new MonologServiceProvider(), [
            'monolog.name' => 'VersionWarner'
        ]);
        $this->app->extend('monolog', function (Logger $monolog, Application $app) {
            $monolog->pushHandler(new StreamHandler('php://stdout'));
            $monolog->pushHandler(new RotatingFileHandler(DIR_ROOT . '/var/logs/log', 0, Logger::NOTICE));
            return $monolog;
        });
        $this->logger = $this->app['monolog'];

        $this->logger->debug('Logger initialized.');

        // Establish database connection
        $config = require_once DIR_ROOT . '/config.php';

        $app['debug'] = $config['debug'];

        $dboptions = [
            'driver' => 'pdo_mysql',
            'dbname' => $config['database']['database'],
            'host' => $config['database']['host'],
            'user' => $config['database']['username'],
            'password' => $config['database']['password'],
            'charset' => 'utf8'
        ];

        $this->app->register(new DoctrineServiceProvider(), [
            'db.options' => $dboptions
        ]);
        $this->db = $this->app['db'];

        $loader = require DIR_ROOT . '/vendor/autoload.php';
        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

        $ormOptions = [
            'orm.proxies_dir' => DIR_ROOT . '/var/tmp/orm',
            'orm.em.options' => [
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'ozzyfant\VersionWarner\Entities',
                        'path' => DIR_ROOT . '/src/Entities',
                        'use_simple_annotation_reader' => false
                    ]
                ]
            ]
        ];

        // Activate APC-cache when not in debug
        if (!$app['debug'] && extension_loaded('apc') && ini_get('apc.enabled')) {
            $ormOptions['orm.default_cache'] = 'apc';
        }

        $this->app->register(new DoctrineOrmServiceProvider(), $ormOptions);

        $this->em = $this->app['orm.em'];
    }

    public function runConsole(): void
    {

        $this->app->register(new ConsoleServiceProvider(), [
            'console.name' => 'Version Warner',
            'console.version' => self::VERSION,
            'console.project_directory' => DIR_ROOT
        ]);

        $console = $this->app['console'];

        // Register commands
        $console->add(new RunCommand($this));

        $console->run();

    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @return Recipient[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return Connection
     */
    public function getDb(): Connection
    {
        return $this->db;
    }

    /**
     * @return EntityManager
     */
    public function getEm(): EntityManager
    {
        return $this->em;
    }


}