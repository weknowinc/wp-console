<?php

/**
 * @file
 * Contains \WP\Console\Command\Multisite\NewCommand.
 */

namespace WP\Console\Command\Multisite;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Core\Generator\SilentIndexGenerator;
use WP\Console\Core\Utils\ArgvInputReader;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\DatabaseTrait;

class NewCommand extends Command
{
    use CommandTrait;
    use DatabaseTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var  ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var SilentIndexGenerator
     */
    protected $generator;


    /**
     * InstallCommand constructor.
     *
     * @param Site                 $site
     * @param ConfigurationManager $configurationManager
     * @param string               $appRoot
     * @param SilentIndexGenerator $generator
     */
    public function __construct(
        Site $site,
        ConfigurationManager $configurationManager,
        $appRoot,
        SilentIndexGenerator $generator
    ) {
        $this->site = $site;
        $this->configurationManager = $configurationManager;
        $this->appRoot = $appRoot;
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('multisite:new')
            ->setDescription($this->trans('commands.multisite.new.description'))
            ->addOption(
                'network-url',
                '/',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.multisite.new.options.network-base')
            )
            ->addOption(
                'network-title',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.multisite.new.options.network-title')
            )
            ->addOption(
                'langcode',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.multisite.new.options.langcode')
            )
            ->addOption(
                'network-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.multisite.new.options.network-mail')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $subdomains = $this->site->isMulsiteSubdomain();

        // --network-url option
        $networkUrl = $input->getOption('network-url');
        if (!$networkUrl) {
            if ($subdomains) {
                $exampleURL = $this->trans('commands.multisite.new.questions.subdomain-example-network-url') . "." . $this->site->getDomain();
            } else {
                $exampleURL = $this->site->getDomain() ."/" . $this->trans('commands.multisite.new.questions.subdirectory-example-network-url');
            }
            $networkUrl = $io->ask(
                $this->trans('commands.multisite.new.questions.network-url'),
                $exampleURL
            );


            $input->setOption('network-url', $networkUrl);
        }

        // --network-title option
        $networkTitle = $input->getOption('network-title');

        if (!$networkTitle) {
            $networkTitle = $io->ask(
                $this->trans('commands.multisite.install.questions.network-title'),
                ''
            );
            $input->setOption('network-title', $networkTitle);
        }

        // --langcode option
        $langcode = $input->getOption('langcode');
        if (!$langcode) {
            $languages = $this->site->getLanguages();
            $defaultLanguage = $this->configurationManager
                ->getConfiguration()
                ->get('application.language');

            $langcode = $io->choiceNoList(
                $this->trans('commands.site.install.questions.langcode'),
                array_values($languages),
                $languages[$defaultLanguage]
            );

            $input->setOption('langcode', array_search($langcode, $languages));
        }

        // --network-mail option
        $networkMail = $input->getOption('network-mail');
        if (!$networkMail) {
            $networkMail = $io->ask(
                $this->trans('commands.multisite.install.questions.network-mail'),
                $this->site->getEmail()
            );
            $input->setOption('network-mail', $networkMail);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $wpdb;
        $currentUser = $this->site->getCurrentUser();

        $io = new WPStyle($input, $output);
        $subdomains = $this->site->isMulsiteSubdomain();

        $networkUrl = $input->getOption('network-url');
        $networkTitle = $input->getOption('network-title');
        $networkEmail = $input->getOption('network-mail');
        $langcode = $input->getOption('langcode');

        $currentSite = $this->site->getCurrentSite();

        if ($subdomains) {
            $newdomain = $networkUrl . "." . $this->site->getDomain();
            $path = $currentSite->path;
        } else {
            $subdirectory_reserved_names = $this->site->getSubdirectoryReservedNames();

            // If not a subdomain install, make sure the domain isn't a reserved word
            if (in_array($networkUrl, $subdirectory_reserved_names)) {
                $io->error(
                    $this->trans('commands.common.messages.reserved')
                );
                return false;
            }

            $path = $currentSite->path . $networkUrl;
            $newdomain = $this->site->getDomain();
        }

        $meta = array(
            'public' => 1
        );

        // Handle translation install for the new site.
        if (isset($langcode)) {
            if ('' === $langcode) {
                $meta['WPLANG'] = ''; // en_US
            } elseif ($this->site->canInstallLanguagePack()) {
                $language = $this->site->downloadLanguagePack($this->site->unslash($langcode));
                if ($language) {
                    $meta['WPLANG'] = $language;
                }
            }
        }

        $userID = $this->site->emailExists($networkEmail);
        if (!$userID) {
            // Create a new user with a random password
            $this->site->doAction('pre_network_site_new_created_user', $networkEmail);

            $userID = $this->site->usernameExists($networkUrl);
            if ($userID) {
                $io->error(
                    $this->trans('commands.multisite.new.user-conflict')
                );
                return false;
            }

            $password = $this->site->generatePassword(12, false);
            $userID = $this->site->createMultisiteUser($networkUrl, $password, $networkEmail);
            if (false === $userID) {
                $io->error(
                    $this->trans('commands.common.messages.user-create-error')
                );
                return false;
            }

            $this->site->doAction('network_site_new_created_user', $userID);
        }

        $id = $this->site->createMultisiteBlog($newdomain, $path, $networkTitle, $userID, $meta, $this->site->getCurrentNetworkID());
        $wpdb->show_errors();
        if (! is_wp_error($id)) {
            if (!$this->site->isSuperAdmin($userID) && !$this->site->getUserOption('primary_blog', $userID)) {
                $this->site->updateUserOption($userID, 'primary_blog', $id, true);
            }

            $this->site->mail(
                $this->site->getOption('admin_email'),
                sprintf(
                    $this->trans('commands.multisite.new.messages.new-site-created'),
                    $networkTitle
                ),
                sprintf(
                    $this->trans('commands.multisite.new.messages.new-site-created-by'),
                    $currentUser->user_login,
                    $this->site->getSiteUrl($id),
                    $this->site->unslash($networkTitle)
                ),
                sprintf(
                    $this->trans('commands.multisite.new.messages.email-from'),
                    $this->site->getOption('admin_email')
                )
            );

            $this->site->multisiteWelcomeNotification($id, $userID, $password, $networkTitle, array( 'public' => 1 ));

            // Check if WP_CONTENT_DIR exist
            if (!is_dir(WP_CONTENT_DIR)) {
                $this->generator->generate($this->appRoot, WP_CONTENT_DIR);
                $this->generator->generate($this->appRoot, WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins');
                $this->generator->generate($this->appRoot, WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes');
                $io->info(
                    $this->trans('commands.multisite.new.messages.content-dir-created')
                );
            }

            $io->info(
                $this->trans('commands.multisite.new.messages.created')
            );
            exit;
        } else {
            $io->error(
                $id->get_error_message()
            );
            return false;
        }
    }
}
