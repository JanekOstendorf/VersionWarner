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
use ozzyfant\VersionWarner\Entities\Recipient;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Twig_Environment;

class VersionWarner
{
    const VERSION = '0.1-dev';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Application
     */
    protected $app;

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
     * @var Twig_Environment
     */
    protected $template;

    /**
     * @var \Swift_Mailer
     */
    protected $email;

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
        $this->app->extend('monolog', function (Logger $monolog) {
            $monolog->pushHandler(new StreamHandler('php://stdout'));
            $monolog->pushHandler(new RotatingFileHandler(DIR_ROOT . '/var/logs/log', 0, Logger::NOTICE));
            return $monolog;
        });
        $this->logger = $this->app['monolog'];

        $this->logger->debug('Logger initialized.');

        // Establish database connection
        $this->config = require_once DIR_ROOT . '/config.php';

        $app['debug'] = $this->config['debug'];
        define('DEBUG', $this->config['debug']);

        $dboptions = [
            'driver' => $this->config['database']['driver'],
            'dbname' => $this->config['database']['database'],
            'host' => $this->config['database']['host'],
            'user' => $this->config['database']['username'],
            'password' => $this->config['database']['password'],
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

        // Initialize Twig (used for E-Mail and Web)
        $this->app->register(new TwigServiceProvider(), [
            'twig.path' => DIR_ROOT . '/assets/templates/email/',
            'twig.options' => [
                'cache' => (DEBUG ? false : DIR_ROOT . '/var/tmp/twig/'),
                'debug' => DEBUG
            ]
        ]);

        $this->template = $this->app['twig'];

        // Inizialize Swift Mailer
        $this->app->register(new SwiftmailerServiceProvider());
        $this->app['swiftmailer.options'] = $this->config['email']['options'];
        $this->app['swiftmailer.sender_address'] = $this->config['email']['sender_address'];
        if (DEBUG) {
            $this->app['swiftmailer.delivery_addresses'] = $this->config['email']['delivery_addresses'];
        }
        $this->email = $this->app['mailer'];
    }

    public function runConsole(): void
    {

        $this->app->register(new ConsoleServiceProvider(), [
            'console.name' => 'Version Warner',
            'console.version' => self::VERSION,
            'console.project_directory' => DIR_ROOT
        ]);

        // Prevent Mailer from spooling
        // Would wait for us to send a response then, which we don't do in console
        $this->app['swiftmailer.use_spool'] = false;

        $console = $this->app['console'];

        // Register commands
        $console->add(new RunCommand($this));
        $console->run();

    }

    /**
     * Flushes the email queue, forcing it to send now
     */
    public function flushEmailQueue(): void
    {
        $this->app['swiftmailer.spooltransport']->getSpool()
            ->flushQueue($this->app['swiftmailer.transport']);
    }

    /**
     * Flushes all entities
     */
    public function flushEm(): void
    {
        $this->getEm()->flush();
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

    /**
     * @return Twig_Environment
     */
    public function getTemplate(): Twig_Environment
    {
        return $this->template;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getEmail(): \Swift_Mailer
    {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }


}