<?php

/**
 * @file
 * Contains \WP\AppConsole\Command\Multisite\InstallCommand.
 */

namespace WP\Console\Command\Multisite;

use Anolilab\Wordpress\SaltGenerator\Generator as SaltGenerator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use WP\Console\Core\Utils\ArgvInputReader;
use WP\Console\Core\Generator\SiteInstallGenerator;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\DatabaseTrait;
use WP\Console\Core\Utils\ChainQueue;

class InstallCommand extends Command
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
     * @var SiteInstallGenerator
     */
    protected $generator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var String
     */
    protected $subdomains;

    /**
     * @var String
     */
    protected $networkTitle;

    /**
     * @var String
     */
    protected $networkEmail;

    /**
     * @var String
     */
    protected $networkBase;


    /**
     * InstallCommand constructor.
     *
     * @param Site                 $site
     * @param ConfigurationManager $configurationManager
     * @param string               $appRoot
     * @param SiteInstallGenerator $generator
     * @param ChainQueue           $chainQueue
     */
    public function __construct(
        Site $site,
        ConfigurationManager $configurationManager,
        $appRoot,
        SiteInstallGenerator $generator,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->configurationManager = $configurationManager;
        $this->appRoot = $appRoot;
        $this->generator = $generator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('multisite:install')
            ->setDescription($this->trans('commands.multisite:install.description'))
            ->addOption(
                'langcode',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.langcode')
            )
            ->addOption(
                'db-host',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.db-host')
            )
            ->addOption(
                'db-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.db-name')
            )
            ->addOption(
                'db-user',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.db-user')
            )
            ->addOption(
                'db-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                'wp_',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.db-prefix')
            )
            ->addOption(
                'site-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.site-name')
            )
            ->addOption(
                'account-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.account-name')
            )
            ->addOption(
                'account-mail',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.account-mail')
            )
            ->addOption(
                'account-pass',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.account-pass')
            )
            ->addOption(
                'subdomains',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.site.install.options.subdomains')
            )
            ->addOption(
                'network-title',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.network-title')
            )
            ->addOption(
                'network-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.network-mail')
            )
            ->addOption(
                'network-base',
                '/',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.network-base')
            )
            ->addOption(
                'force',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.site.install.options.force')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $argvInputReader = new ArgvInputReader();

        if (!$this->site->isInstalled()) {
            // --uri parameter
            $uri =  parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_HOST);
            $scheme =  parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_SCHEME);
            if ($uri == 'default') {
                $siteUri = $io->ask(
                    $this->trans('commands.site.install.questions.site-url'),
                    'http://wordpress.local'
                );
                if ($siteUri != 'default') {
                    $uri =  parse_url($siteUri, PHP_URL_HOST);
                    $scheme =  parse_url($siteUri, PHP_URL_SCHEME);
                    $argvInputReader->setOptionsFromConfiguration(['--uri'=>$siteUri]);
                }
            }

            $this->site->setSiteURL($scheme, $uri);

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

            // --db-host option
            /*$dbHost = $input->getOption('db-host');
            if (!$dbHost) {
                $dbHost = $this->dbHostQuestion($io);
                $input->setOption('db-host', $dbHost);
            }*/

            // --db-name option
            $dbName = $input->getOption('db-name');
            if (!$dbName) {
                $dbName = $this->dbNameQuestion($io);
                $input->setOption('db-name', $dbName);
            }

            // --db-user option
            $dbUser = $input->getOption('db-user');
            if (!$dbUser) {
                $dbUser = $this->dbUserQuestion($io);
                $input->setOption('db-user', $dbUser);
            }

            // --db-pass option
            $dbPass = $input->getOption('db-pass');
            if (!$dbPass) {
                $dbPass = $this->dbPassQuestion($io);
                $input->setOption('db-pass', $dbPass);
            }

            // --db-port option
            /*$dbPort = $input->getOption('db-port');
            if (!$dbPort) {
                $dbPort = $this->dbPortQuestion($io);
                $input->setOption('db-port', $dbPort);
            }*/


            // --db-prefix option
            $dbPrefix = $input->getOption('db-prefix');
            if (!$dbPrefix) {
                $dbPrefix = $this->dbPrefixQuestion($io);
                $input->setOption('db-prefix', $dbPrefix);
            }

            // --site-name option
            $siteName = $input->getOption('site-name');
            if (!$siteName) {
                $siteName = $io->ask(
                    $this->trans('commands.site.install.questions.site-name'),
                    'Wordpress'
                );
                $input->setOption('site-name', $siteName);
            }

            // --account-name option
            $accountName = $input->getOption('account-name');
            if (!$accountName) {
                $accountName = $io->ask(
                    $this->trans('commands.site.install.questions.account-name'),
                    'admin'
                );
                $input->setOption('account-name', $accountName);
            }

            // --account-mail option
            $accountMail = $input->getOption('account-mail');
            if (!$accountMail) {
                $accountMail = $io->ask(
                    $this->trans('commands.site.install.questions.account-mail'),
                    'admin@example.com'
                );
                $input->setOption('account-mail', $accountMail);
            }

            // --account-pass option
            $accountPass = $input->getOption('account-pass');
            if (!$accountPass) {
                $accountPass = $io->askHidden(
                    $this->trans('commands.site.install.questions.account-pass')
                );
                $input->setOption('account-pass', $accountPass);
            }

            $this->interactiveMultisiteQuestions($input, $io);
        } elseif (!$this->site->isMultisite()) {
            $this->interactiveMultisiteQuestions($input, $io);
        } else {
            print 'here';
            $io->info(
                $this->trans('commands.site.install.messages.already-multisite')
            );
            exit();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $wpdb;

        $io = new WPStyle($input, $output);

        $force = $input->getOption('force');


        if (!$this->site->isInstalled()) {
            $saltGenerator = new SaltGenerator();

            $uri = parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_HOST);
            $scheme = parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_SCHEME);

            $this->site->setSiteURL($scheme, $uri);

            if ($this->site->getConfig()) {
                $io->error(
                    sprintf($this->trans('commands.site.install.messages.already-installed'), $uri, $uri)
                );
                exit(1);
            }

            $siteName = $input->getOption('site-name');
            $accountName = $input->getOption('account-name');
            $accountMail = $input->getOption('account-mail');
            $accountPass = $input->getOption('account-pass');
            $dbHost = $input->getOption('db-host') ?: '127.0.0.1';
            $dbName = $input->getOption('db-name') ?: 'drupal_' . time();
            $dbUser = $input->getOption('db-user') ?: 'root';
            $dbPass = $input->getOption('db-pass');
            $langcode = $input->getOption('langcode');
            $dbPrefix = $input->getOption('db-prefix');

            $this->multisiteQuestions($input);

            $siteInstallcommand = $this->getApplication()->find('site:install');
            $arguments = [
                '--langcode' => $langcode,
                '--db-host' => $dbHost,
                '--db-name' => $dbName,
                '--db-user' => $dbUser,
                '--db-pass' => $dbPass,
                '--db-prefix' => $dbPrefix,
                '--site-name' => $siteName,
                '--account-name' => $accountName,
                '--account-mail' => $accountMail,
                '--account-pass' => $accountPass,
                '--uri' => $scheme. "://" . $uri,
                '--force' => $force
            ];

            $siteInstallInput = new ArrayInput($arguments);
            $siteInstallcommand->run($siteInstallInput, $io);

            $io->info(
                $this->trans('commands.multisite.install.messages.installing')
            );

            $contants = $this->site->extractConstants($this->appRoot . '/wp-config.php');

            $configParameters = array(
                'dbhost' => $dbHost,
                'dbname' => $dbName,
                'dbuser' => $dbUser,
                'dbpass' => $dbPass,
                'dbprefix' => $dbPrefix,
                'dbcharset' => $contants['DB_CHARSET'],
                'dbcollate' => $contants['DB_COLLATE'],
                'locale' => $langcode,
                'authKey' => $contants['AUTH_KEY'],
                'secureAuthKey' => $contants['SECURE_AUTH_KEY'],
                'loggedInKey' => $contants['LOGGED_IN_KEY'],
                'NonceKey' => $contants['NONCE_KEY'],
                'authSalt' => $contants['AUTH_SALT'],
                'secureSalt' => $contants['SECURE_AUTH_SALT'],
                'loggedInSalt' => $contants['LOGGED_IN_SALT'],
                'NonceSalt' => $contants['NONCE_SALT'],
                'multisite' => 'true',
                'subdomains' => $this->subdomains,
                'domain' => $uri,
                'base' => $this->networkBase
            );

            $result = $this->runInstaller($io, $uri, $configParameters, $force);

            if ($result) {
                $io->info(
                    $this->trans('commands.multisite.install.messages.installed')
                );
            } else {
                $io->info(
                    $this->trans('commands.multisite.install.messages.error-installing-multisite')
                );
            }
        } elseif (!$this->site->isMultisite()) {
            $domain = $this->site->getDomain();
            $this->multisiteQuestions($input);
            $io->info(
                $this->trans('commands.multisite.install.messages.installing')
            );

            $configParameters = array(
                'dbhost' => DB_HOST,
                'dbname' => DB_NAME,
                'dbuser' => DB_USER,
                'dbpass' => DB_PASSWORD,
                'dbprefix' => $wpdb->prefix,
                'dbcharset' => DB_CHARSET,
                'dbcollate' => '',
                'locale' => WPLANG,
                'authKey' => AUTH_KEY,
                'secureAuthKey' => SECURE_AUTH_KEY,
                'loggedInKey' => LOGGED_IN_KEY,
                'NonceKey' => NONCE_KEY,
                'authSalt' => AUTH_SALT,
                'secureSalt' => SECURE_AUTH_SALT,
                'loggedInSalt' => LOGGED_IN_SALT,
                'NonceSalt' => NONCE_SALT,
                'multisite' => 'true',
                'subdomains' => $this->subdomains,
                'domain' => $domain,
                'base' => $this->networkBase
            );

            $result = $this->runInstaller($io, $domain, $configParameters, $force);

            if ($result) {
                $io->info(
                    $this->trans('commands.multisite.install.messages.installed')
                );
            } else {
                $io->info(
                    $this->trans('commands.multisite.install.messages.error-installing-multisite')
                );
            }
        } else {
            $io->info(
                $this->trans('commands.multisite.install.messages.already-multisite')
            );
        }
    }

    public function generateConfigFile(WPStyle $io, $parameters, $force)
    {
        $this->generator->generate(
            $this->appRoot,
            $force,
            $parameters
        );

        if ($force && file_exists($this->appRoot . "/wp-config.php.old")) {
            $io->error(
                $this->trans('commands.site.install.messages.config-overwrite')
            );
        }
    }

    public function runInstaller(WPStyle $io, $domain, $parameters, $force)
    {
        global $wpdb;

        $this->generateConfigFile($io, $parameters, $force);

        $this->site->loadLegacyFile('wp-admin/includes/upgrade.php');

        if ($domain === 'localhost' && !$this->subdomains) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.multisite.install.messages.invalid-domain'),
                    $domain
                )
            );
        }

        // Create references to ms global tables to enable Network.
        $tables = $this->site->getTables('ms_global');
        foreach ($tables  as $table => $prefixed_table) {
            $wpdb->$table = $prefixed_table;
        }

        foreach ($wpdb->tables('ms_global') as $table => $prefixed_table) {
            $wpdb->$table = $prefixed_table;
        }

        install_network();

        $result = populate_network(
            1, //Site Id
            $domain,
            $this->networkEmail,
            $this->networkTitle,
            '/', // Base path
            $this->subdomains
        );

        if ($result) {
            $io->info(
                $this->trans('commands.multisite.install.messages.setup-tables')
            );
        } elseif (is_wp_error($result)) {
            switch ($result->get_error_code()) {
            case 'siteid_exists':
                WP_CLI::log($result->get_error_message());
                $io->error(
                    $result->get_error_message()
                );
                return false;
            case 'no_wildcard_dns':
                $io->info(
                    $this->trans('commands.site.multisite.install.messages.no-wildcard-dns')
                );
                break;
            default:
                $io->error(
                    $result->get_error_message()
                );
                return false;
            }
        }

        $this->site->deleteOption('upload_space_check_disabled');
        $this->site->updateOption('upload_space_check_disabled', 1);
        $user = $this->site->getUserByField('email', $this->networkEmail);
        $this->site->addSiteAdmin($user);

        $this->site->createNetwork(1, 1, $domain, $this->networkBase, $this->subdomains, $user);

        return true;
    }

    protected function multisiteQuestions(ArgvInput $input)
    {
        // --subdomains option
        $this->subdomains = $input->getOption('subdomains');

        // --network-title option
        $this->networkTitle = $input->getOption('network-title');

        // --network-mail option
        $this->networkEmail = $input->getOption('network-mail');

        // --network-mail option
        $this->networkBase = $input->getOption('network-base');
    }

    protected function interactiveMultisiteQuestions(ArgvInput $input, WPStyle $io)
    {
        $subdomains = $input->getOption('subdomains');

        if (!$subdomains) {
            $types = [
                0 => $this->trans('commands.multisite.install.questions.subdomains.subdirectories'),
                1 => $this->trans('commands.multisite.install.questions.subdomains.subdomains')
            ];

            $subdomainType = $io->choice(
                $this->trans('commands.multisite.install.questions.subdomains.question'),
                $types
            );

            $subdomains = array_search($subdomainType, $types);

            $input->setOption('subdomains', $subdomains);
        }

        // --network-title option
        $networkTitle = $input->getOption('network-title');

        if (!$networkTitle) {
            $networkTitle = $io->ask(
                $this->trans('commands.multisite.install.questions.network-title'),
                sprintf('%s Sites', $this->site->getTitle())
            );
            $input->setOption('network-title', $networkTitle);
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

        // --base-path option
        $networkBase = $input->getOption('network-base');
        if (!$networkBase) {
            $networkBase = $io->ask(
                $this->trans('commands.multisite.install.questions.network-base'),
                '/'
            );
            $input->setOption('network-base', $networkBase);
        }
    }
}
