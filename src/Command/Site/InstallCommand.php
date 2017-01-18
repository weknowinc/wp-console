<?php

/**
 * @file
 * Contains \WP\AppConsole\Command\Site\InstallCommand.
 */

namespace WP\Console\Command\Site;

use Anolilab\Wordpress\SaltGenerator\Generator as SaltGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
/*use Drupal\Core\Database\Database;
use Drupal\Core\Installer\Exception\AlreadyInstalledException;*/
use WP\Console\Core\Generator\SiteInstallGenerator;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Bootstrap\Wordpress;
use WP\Console\Utils\Site;
use WP\Console\Helper\WordpressFinder;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\DatabaseTrait;
use WP\Console\Core\Utils\ArgvInputReader;

class InstallCommand extends Command
{
    use CommandTrait;
    use DatabaseTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

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
     * InstallCommand constructor.
     *
     * @param Manager              $extensionManager
     * @param Site                 $site
     * @param ConfigurationManager $configurationManager
     * @param string               $appRoot
     * @param SiteInstallGenerator $generator
     */
    public function __construct(
        Manager $extensionManager,
        Site $site,
        ConfigurationManager $configurationManager,
        $appRoot,
        SiteInstallGenerator $generator
    ) {
        $this->extensionManager = $extensionManager;
        $this->site = $site;
        $this->configurationManager = $configurationManager;
        $this->appRoot = $appRoot;
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('site:install')
            ->setDescription($this->trans('commands.site.install.description'))
            ->addOption(
                'langcode',
                '',
                InputOption::VALUE_REQUIRED,
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
                'db-port',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.db-port')
            )
            ->addOption(
                'site-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.site-name')
            )
            ->addOption(
                'account-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.account-name')
            )
            ->addOption(
                'account-mail',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.account-mail')
            )
            ->addOption(
                'account-pass',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.site.install.options.account-pass')
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

        // --uri parameter
        $uri =  parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_HOST);
        $scheme =  parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_SCHEME);
        if($uri == 'default') {
            $siteUri = $io->ask(
                $this->trans('commands.site.install.questions.site-url'),
                'http://wordpress.local'
            );
            if($siteUri != 'default') {
                $uri =  parse_url($siteUri, PHP_URL_HOST);
                $scheme =  parse_url($siteUri, PHP_URL_SCHEME);
                $argvInputReader->setOptionsFromConfiguration(['--uri'=>$siteUri]);
            }
        }

        $this->setSiteURL($scheme, $uri);

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

            $input->setOption('langcode', $langcode);
        }

        // --db-host option
        $dbHost = $input->getOption('db-host');
        if (!$dbHost) {
            $dbHost = $this->dbHostQuestion($io);
            $input->setOption('db-host', $dbHost);
        }

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
        $dbPort = $input->getOption('db-port');
        if (!$dbPort) {
            $dbPort = $this->dbPortQuestion($io);
            $input->setOption('db-port', $dbPort);
        }


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
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $saltGenerator = new SaltGenerator();

        $uri =  parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_HOST);
        $scheme =  parse_url($input->getParameterOption(['--uri', '-l'], 'http://default'), PHP_URL_SCHEME);

        $this->setSiteURL($scheme, $uri);

        if($this->site->getConfig()) {
            $io->error(
                sprintf($this->trans('commands.site.install.messages.already-installed'), $uri, $uri)
            );
            exit(1);
        }

        $siteName = $input->getOption('site-name');
        $accountName = $input->getOption('account-name');
        $accountMail = $input->getOption('account-mail');
        $accountPass = $input->getOption('account-pass');
        $dbHost = $input->getOption('db-host')?:'127.0.0.1';
        $dbName = $input->getOption('db-name')?:'drupal_'.time();
        $dbUser = $input->getOption('db-user')?:'root';
        $dbPass = $input->getOption('db-pass');
        $langcode = $input->getOption('langcode');
        $dbPrefix = $input->getOption('db-prefix');
        $force = $input->getOption('force');

        $configParameters = array(
            'dbhost' => $dbHost,
            'dbname' => $dbName,
            'dbuser' => $dbUser,
            'dbpass' => $dbPass,
            'dbprefix' => $dbPrefix,
            'dbcharset' => 'utf8',
            'dbcollate' => '',
            'locale' => $langcode,
            'authKey' => $saltGenerator->generateSalt(),
            'secureAuthKey' => $saltGenerator->generateSalt(),
            'loggedInKey' => $saltGenerator->generateSalt(),
            'NonceKey' => $saltGenerator->generateSalt(),
            'authSalt' => $saltGenerator->generateSalt(),
            'secureSalt' => $saltGenerator->generateSalt(),
            'loggedInSalt' => $saltGenerator->generateSalt(),
            'NonceSalt' => $saltGenerator->generateSalt(),
        );

        $this->generator->generate(
            $this->appRoot,
            $force,
            $configParameters
        );

        if($force && file_exists($this->appRoot . "/wp-config.php.old")) {
            $io->error(
                $this->trans('commands.site.install.messages.config-overwrite')
            );
        }

        $io->info(
            $this->trans('commands.site.install.messages.installing')
        );

        $this->runInstaller($siteName, $accountName, $accountMail, true, $accountPass);

        $io->info(
            $this->trans('commands.site.install.messages.installed')
        );
    }

    public function runInstaller($siteName, $accountName, $accountMail, $public, $accountPass) {

        if(!defined( 'WP_INSTALLING' ) ) {
            define('WP_INSTALLING', true);
        }

        $this->site->loadLegacyFile('wp-config.php');
        $this->site->loadLegacyFile('wp-admin/includes/upgrade.php' );

        $result = wp_install( $siteName, $accountName, $accountMail, $public, '', $accountPass );

        return !empty($result);
    }

    protected function setSiteURL($scheme, $uri) {
/*        print "Scheme:" . $scheme . "\n";
        print "Uri:" . $uri . "\n";*/
        $_SERVER['SERVER_NAME'] = $uri;

        if(!defined( 'WP_SITEURL' ) ) {
            define('WP_SITEURL', $scheme . '://' . $uri);
        }
    }

}
