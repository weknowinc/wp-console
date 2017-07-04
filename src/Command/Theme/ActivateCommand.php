<?php

/**
 * @file
 * Contains \WP\Console\Command\Theme\ActivateCommand.
 */

namespace WP\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\ThemeTrait;
use WP\Console\Utils\Site;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;
use WP\Console\Extension\Manager;
use WP\Console\Core\Utils\ChainQueue;

/**
 * Class ActivateCommand
 *
 * @package WP\Console\Command\Theme
 */
class ActivateCommand extends Command
{
    use CommandTrait;
    use ThemeTrait;
    
    /**
     * @var Site
     */
    protected $site;
    
    /**
     * @var Validator
     */
    protected $validator;
    
    /**
     * @var Manager
     */
    protected $extensionManager;
    
    /**
     * @var string
     */
    protected $appRoot;
    
    /**
     * @var ChainQueue
     */
    protected $chainQueue;
    
    /**
     * InstallCommand constructor.
     *
     * @param Site       $site
     * @param Validator  $validator
     * @param Manager    $extensionManager
     * @param $appRoot
     * @param ChainQueue $chainQueue
     */
    public function __construct(
        Site $site,
        Validator $validator,
        Manager $extensionManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->validator = $validator;
        $this->extensionManager = $extensionManager;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('theme:activate')
            ->setDescription($this->trans('commands.theme.activate.description'))
            ->addArgument(
                'theme',
                InputArgument::IS_ARRAY,
                $this->trans('commands.theme.activate.arguments.theme')
            )
            ->setAliases(['ta']);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        $theme = $input->getArgument('theme');
        if (!$theme) {
            $theme = $this->themeQuestion($io, false);
            $input->setArgument('theme', $theme);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        $theme = $input->getArgument('theme');
        
        try {
            $extensions = $this->extensionManager->discoverthemes()->showDeactivated()->getList();
            foreach ($extensions as $key => $value) {
                if ($value->get('Name') == $theme) {
                    $theme = $value->stylesheet;
                }
            }
            
            $this->site->activateTheme($theme);
            
            $io->success(
                sprintf(
                    $this->trans('commands.theme.activate.messages.success'),
                    $theme
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            
            return 1;
        }
        
        // $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
