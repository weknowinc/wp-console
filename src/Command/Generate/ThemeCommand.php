<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\ThemeCommand.
 */

namespace WP\Console\Command\Generate;

use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Generator\ThemeGenerator;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Utils\Site;

class ThemeCommand extends Command
{
    use ConfirmationTrait;
    use CommandTrait;
    
    /**
     * @var ThemeGenerator
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
     * ThemeCommand constructor.
     *
     * @param ThemeGenerator  $generator
     * @param Validator       $validator
     * @param $appRoot
     * @param StringConverter $stringConverter
     * @param Client          $httpClient
     * @param Site            $site
     * @param $twigtemplate
     */
    public function __construct(
        ThemeGenerator $generator,
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
            ->setName('generate:theme')
            ->setDescription($this->trans('commands.generate.theme.description'))
            ->setHelp($this->trans('commands.generate.theme.help'))
            ->addOption(
                'theme',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.theme')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.machine-name')
            )
            ->addOption(
                'theme-path',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.theme-path')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.module.options.description')
            )
            ->addOption(
                'author',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.author')
            )
            ->addOption(
                'author-url',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.theme.options.author-url')
            )
            ->addOption(
                'screenshot',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.screenshot')
            )->addOption(
                'template-files',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.theme.options.template-files')
            )
            ->setAliases(['gth']);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;
        
        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }
        
        $theme = $this->validator->validatePluginName($input->getOption('theme'));
        
        $themePath = $this->appRoot . $input->getOption('theme-path');
        $themePath = $this->validator->validatePluginPath($themePath, true);
        
        $machineName = $this->validator->validateMachineName($input->getOption('machine-name'));
        $description = $input->getOption('description');
        $author = $input->getOption('author');
        $authorURL = $input->getOption('author-url');
        $template_files = $input->getOption('template-files');
        $screenshot = $input->getOption('screenshot');
        // $test = $input->getOption('test');
        
        $package = str_replace(' ', '_', $theme);
        
        
        $this->generator->generate(
            $this->site,
            $theme,
            $machineName,
            $themePath,
            $description,
            $author,
            $authorURL,
            $template_files,
            $screenshot,
            $package,
            $test
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        $validator = $this->validator;
        
        try {
            $theme = $input->getOption('theme') ?
                $this->validator->validatePluginName(
                    $input->getOption('theme')
                ) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());
            
            return;
        }
        
        // --theme
        if (!$theme) {
            $theme = $io->ask(
                $this->trans('commands.generate.theme.questions.theme'),
                null,
                function ($theme) use ($validator) {
                    return $validator->validatePluginName($theme);
                }
            );
            $input->setOption('theme', $theme);
        }
        
        try {
            $machineName = $input->getOption('machine-name') ?
                $this->validator->validatePluginName(
                    $input->getOption('machine-name')
                ) : null;
        } catch (\Exception $error) {
            $io->error($error->getMessage());
        }
        
        // --machine name
        if (!$machineName) {
            $machineName = $io->ask(
                $this->trans('commands.generate.theme.questions.machine-name'),
                $this->stringConverter->createMachineName($theme),
                function ($machine_name) use ($validator) {
                    return $validator->validateMachineName($machine_name);
                }
            );
            $input->setOption('machine-name', $machineName);
        }
        
        // --theme path
        $themePath = $input->getOption('theme-path');
        if (!$themePath) {
            $wordpressRoot = $this->appRoot;
            $themePath = $io->ask(
                $this->trans('commands.generate.theme.questions.theme-path'),
                basename(WP_CONTENT_DIR) . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $machineName,
                function ($themePath) use ($wordpressRoot, $machineName) {
                    $themePath = ($themePath[0] != '/' ? '/' : '').$themePath;
                    $fullPath = $wordpressRoot.$themePath.'/'.$machineName;
                    if (file_exists($fullPath)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.theme.errors.directory-exists'),
                                $fullPath
                            )
                        );
                    }
                    
                    return $themePath;
                }
            );
        }
        $input->setOption('theme-path', $themePath);
        
        // --description
        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.theme.questions.description'),
                'My Awesome theme'
            );
        }
        $input->setOption('description', $description);
        
        // --author
        $author = $input->getOption('author');
        if (!$author) {
            $author = $io->ask(
                $this->trans('commands.generate.theme.questions.author'),
                ''
            );
        }
        $input->setOption('author', $author);
        
        // --author url
        $authorUrl = $input->getOption('author-url');
        if (!$authorUrl) {
            $authorUrl = $io->ask(
                $this->trans('commands.generate.theme.questions.author-url'),
                ''
            );
        }
        $input->setOption('author-url', $authorUrl);
    
        // -- template files
        $template_files = $input->getOption('template-files');
        if (!$template_files) {
            $options_template_files = ['header', 'footer', 'sidebar', 'front-page', 'home', 'single', 'page', 'category',
            'comments', 'search', '404', 'functions'];
            foreach ($options_template_files as $options) {
                if ($io->confirm(
                    $this->trans('commands.generate.theme.questions.template-'.$options),
                    false
                )
                ) {
                    $template_files [$options] = $options;
                }
            }
        }
        $input->setOption('template-files', $template_files);
    
        // --screenshot
        $screenshot = $input->getOption('screenshot');
        if (!$screenshot) {
            $screenshot = $io->askEmpty(
                $this->trans('commands.generate.theme.questions.screenshot')
            );
        }
        $input->setOption('screenshot', $screenshot);
    }
}
