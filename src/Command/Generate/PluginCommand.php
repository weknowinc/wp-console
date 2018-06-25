<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\PluginCommand.
 */

namespace WP\Console\Command\Generate;

use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\PluginGenerator;
use WP\Console\Utils\Validator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Utils\Site;
use Webmozart\PathUtil\Path;

class PluginCommand extends Command
{
    use ConfirmationTrait;

    /**
     * @var PluginGenerator
     */
    protected $generator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var string
     */
    protected $twigtemplate;

    /**
     * ModuleCommand constructor.
     *
     * @param PluginGenerator $generator
     * @param Validator       $validator
     * @param $appRoot
     * @param StringConverter $stringConverter
     * @param Client          $httpClient
     * @param Site            $site
     * @param $twigtemplate
     */
    public function __construct(
        PluginGenerator $generator,
        Validator $validator,
        $appRoot,
        StringConverter $stringConverter,
        Client $httpClient,
        Site $site,
        $twigtemplate = null
    ) {
        $this->generator = $generator;
        $this->validator = $validator;
        $this->appRoot = $appRoot;
        $this->stringConverter = $stringConverter;
        $this->httpClient = $httpClient;
        $this->site = $site;
        $this->twigtemplate = $twigtemplate;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:plugin')
            ->setDescription($this->trans('commands.generate.plugin.description'))
            ->setHelp($this->trans('commands.generate.plugin.help'))
            ->addOption(
                'plugin',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.options.plugin')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.options.machine-name')
            )
            ->addOption(
                'plugin-path',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.options.plugin-path')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.options.description')
            )
            ->addOption(
                'author',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.options.author')
            )
            ->addOption(
                'author-url',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.options.author-url')
            )
            ->addOption(
                'activate',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.options.activate')
            )
            ->addOption(
                'deactivate',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.options.activate')
            )
            ->addOption(
                'uninstall',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.options.uninstall')
            )
            ->setAliases(['gpl']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $plugin = $this->validator->validatePluginName($input->getOption('plugin'));

        // Get the plugin path and define it if it is null
        // Check that it is an absolute path or otherwise create an absolute path using appRoot
        $pluginPath = $input->getOption('plugin-path');
        $pluginPath = $pluginPath == null ? basename(WP_CONTENT_DIR) . DIRECTORY_SEPARATOR . 'plugins' : $pluginPath;
        $pluginPath = Path::isAbsolute($pluginPath) ? $pluginPath : Path::makeAbsolute($pluginPath, $this->appRoot);
        $pluginPath = $this->validator->validatePluginPath($pluginPath, true);

        $machineName = $this->validator->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $author = $input->getOption('author');
        $authorURL = $input->getOption('author-url');
        $activate = $input->getOption('activate');
        $deactivate = $input->getOption('deactivate');
        $uninstall = $input->getOption('uninstall');

        $package = str_replace(' ', '_', $plugin);

        $className = $this->stringConverter->humanToCamelCase($plugin);

        $this->generator->generate(
            [
            'plugin' => $plugin,
            'plugin_uri' => '',
            'machine_name' => $machineName,
            'type' => 'module',
            'version' => $this->site->getBlogInfo('version'),
            'description' => $description,
            'author' => $author,
            'author_uri' => $authorURL,
            'package' => $package,
            'class_name_base' => $className,
            'activate' => $activate,
            'deactivate' => $deactivate,
            'uninstall' => $uninstall,
            'plugin_path' => $pluginPath
            ]
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator = $this->validator;

        try {
            $plugin = $input->getOption('plugin') ?
                $this->validator->validatePluginName(
                    $input->getOption('plugin')
                ) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());

            return;
        }

        if (!$plugin) {
            $plugin = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.questions.plugin'),
                null,
                function ($plugin) use ($validator) {
                    return $validator->validatePluginName($plugin);
                }
            );
            $input->setOption('plugin', $plugin);
        }

        try {
            $machineName = $input->getOption('machine-name') ?
                $this->validator->validatePluginName(
                    $input->getOption('machine-name')
                ) : null;
        } catch (\Exception $error) {
            $this->getIo()->error($error->getMessage());
        }

        if (!$machineName) {
            $machineName = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.questions.machine-name'),
                $this->stringConverter->createMachineName($plugin),
                function ($machine_name) use ($validator) {
                    return $validator->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machineName);
        }

        $pluginPath = $input->getOption('plugin-path');
        if (!$pluginPath) {
            $pluginPath = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.questions.plugin-path'),
                basename(WP_CONTENT_DIR) . DIRECTORY_SEPARATOR . 'plugins',
                function ($pluginPath) use ($machineName) {
                    $fullPath = Path::isAbsolute($pluginPath) ? $pluginPath : Path::makeAbsolute($pluginPath, $this->appRoot);
                    $fullPath = $fullPath.'/'.$machineName;
                    if (file_exists($fullPath)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.plugin.errors.directory-exists'),
                                $fullPath
                            )
                        );
                    }

                    return $pluginPath;
                }
            );
        }
        $input->setOption('plugin-path', $pluginPath);

        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.questions.description'),
                'My Awesome Plugin'
            );
        }
        $input->setOption('description', $description);

        $author = $input->getOption('author');
        if (!$author) {
            $author = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.questions.author'),
                ''
            );
        }
        $input->setOption('author', $author);

        $authorUrl = $input->getOption('author-url');
        if (!$authorUrl) {
            $authorUrl = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.questions.author-url'),
                ''
            );
        }
        $input->setOption('author-url', $authorUrl);


        $activate = $input->getOption('activate');
        if (!$activate) {
            $activate = $this->getIo()->confirm(
                $this->trans('commands.generate.plugin.questions.activate'),
                true
            );
            $input->setOption('activate', $activate);
        }

        $deactivate = $input->getOption('deactivate');
        if (!$deactivate) {
            $deactivate = $this->getIo()->confirm(
                $this->trans('commands.generate.plugin.questions.deactivate'),
                true
            );
            $input->setOption('deactivate', $deactivate);
        }

        $uninstall = $input->getOption('uninstall');
        if (!$uninstall) {
            $uninstall = $this->getIo()->confirm(
                $this->trans('commands.generate.plugin.questions.uninstall'),
                true
            );
            $input->setOption('uninstall', $uninstall);
        }
    }
}
