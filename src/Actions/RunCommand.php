<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\Actions;

use Knp\Command\Command;
use ozzyfant\VersionWarner\Entities\Recipient;
use ozzyfant\VersionWarner\Entities\VersionCheck;
use ozzyfant\VersionWarner\VersionWarner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    /**
     * @var VersionWarner
     */
    private $app;

    /**
     * RunCommand constructor.
     * @param VersionWarner $app
     */
    public function __construct(VersionWarner $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('run')
            ->setDescription('Runs all checks and sends notifications.')
            ->setHelp('Use this command for your cron job to run the checks automatically.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get all enabled checks and run them
        /** @var VersionCheck[] $versionChecks */
        $versionChecks = $this->app->getEm()->getRepository(VersionCheck::class)->findBy(['enabled' => true]);

        foreach ($versionChecks as $versionCheck) {
            $this->app->getLogger()->info('Processing Check', ['check' => $versionCheck->getName()]);

            $versionCheck->initProvider();

            // Perform checks when we're in debug and when the minimal time interval is up
            if (DEBUG || $versionCheck->checkRunInterval()) {
                $versionCheck->runCheck();
                $this->app->getEm()->persist($versionCheck);

                if ($versionCheck->isNotifying()) {
                    $notification = $versionCheck->getNotification();
                    $this->app->getLogger()->info('New Version found', ['check' => $versionCheck->getName(), 'version' => $versionCheck->getNotification()->getNewVersion()->getVersion()]);

                    /** @var Recipient[] $recipients */
                    $recipients = $versionCheck->getRecipients()->filter(function (Recipient $entry) {
                        return $entry->isEnabled();
                    });

                    foreach ($recipients as $recipient) {
                        $recipient->addNotification($notification);
                    }
                }

            } else {
                $this->app->getLogger()->info('Not running check again, because of minimum check interval.', ['check' => $versionCheck->getName()]);
            }

        }

        // Fetch all Recipients
        $this->app->getLogger()->info('Processing notifications');

        /**
         * @var Recipient[] $recipients
         */
        $recipients = $this->app->getEm()->getRepository(Recipient::class)->findBy(['enabled' => true]);

        foreach ($recipients as $recipient) {
            if (sizeof($recipient->getNotifications()) > 0) {

                $notificationsTemplate = [];
                $notificationTitles = [];
                $notificationShortTitles = [];
                $notificationShortTitleVersion = [];

                foreach($recipient->getNotifications() as $notification) {
                    $notificationsTemplate[] = $notification->toTemplateArray();
                    $notificationTitles[] = $notification->getCheck()->getTitle();
                    $notificationShortTitles[] = $notification->getCheck()->getShortTitle();
                    $notificationShortTitleVersion[] = $notification->getCheck()->getShortTitle() . ' ' . $notification->getNewVersion();
                }

                $subject = join(', ', $notificationShortTitleVersion);

                // Initialize Twig
                $html = $this->app->getTemplate()->render('notification.twig', [
                    'notifications' => $notificationsTemplate,
                    'notificationTitles' => $notificationTitles,
                    'notificationShortTitles' => $notificationShortTitles,
                    'recipient' => $recipient->toTemplateArray(),
                    'subject' => $subject,
                    'appname' => $this->app->getConfig()['appname']
                ]);

                $plain = $this->app->getTemplate()->render('notification_plain.twig', [
                    'notifications' => $notificationsTemplate,
                    'notificationTitles' => $notificationTitles,
                    'notificationShortTitles' => $notificationShortTitles,
                    'recipient' => $recipient->toTemplateArray(),
                    'subject' => $subject,
                    'appname' => $this->app->getConfig()['appname']
                ]);

                $email = \Swift_Message::newInstance()
                    ->setSubject('[' . $this->app->getConfig()['appname'] . '] ' . $subject)
                    ->setTo($recipient->getEmail())
                    ->setFrom([
                        $this->app->getConfig()['email']['sender_address'] => $this->app->getConfig()['email']['sender_name']
                    ])
                    ->setBody($html, 'text/html')
                    ->addPart($plain, 'text/plain');

                $this->app->getEmail()->send($email);

            }
        }

        $this->app->flushEm();
        $this->app->flushEmailQueue();

    }

}