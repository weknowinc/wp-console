<?php

/**
 * @file
 * Contains \WP\Console\Command\Debug\ThemeCommand.
 */

namespace WP\Console\Command\Debug;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Extension\Manager;

class ThemeCommand extends Command
{
    use CommandTrait;
    
    /**
     * @var Manager
     */
    protected $extensionManager;
    
    /**
     * @var Site
     */
    protected $site;
    
    
    /**
     * DebugCommand constructor.
     *
     * @param Manager $extensionManager
     * @param Site    $site
     */
    public function __construct(
        Manager $extensionManager,
        Site $site
    ) {
        $this->extensionManager = $extensionManager;
        $this->site = $site;
        parent::__construct();
    }
    
    protected function configure()
    {
        $this
            ->setName('debug:theme')
            ->setDescription($this->trans('commands.debug.theme.description'))
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.theme.options.status')
            )
            ->setAliases(['dt']);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        $status = $input->getOption('status');
        
        $tableHeader = [
            $this->trans('commands.debug.theme.messages.name'),
            $this->trans('commands.debug.theme.messages.status'),
            $this->trans('commands.debug.theme.messages.theme-uri'),
            $this->trans('commands.debug.theme.messages.version'),
            $this->trans('commands.debug.theme.messages.author'),
            $this->trans('commands.debug.theme.messages.author-url')
        ];
    
        $tableRows = [];
        
        $discoverThemes = $this->extensionManager->discoverThemes();
        if ($status == 'disabled') {
            $discoverThemes->showDeactivated();
        } elseif ($status == 'enabled') {
            $discoverThemes->showActivated();
        } else {
            $discoverThemes->showActivated()->showDeactivated();
        }
    
        $themes = $discoverThemes->getList();
       
        foreach ($themes as $theme => $themeData) {
            $themeStatus = ($this->site->isThemeActive($theme))?$this->trans('commands.common.status.enabled'): $this->trans('commands.common.status.disabled');
            
            $tableRows [] = [
                $themeData['Name'],
                $themeStatus,
                $themeData['ThemeURI'],
                $themeData['Version'],
                $themeData['Author'],
                $themeData['AuthorURI'],
            ];
        }
        
        $io->table($tableHeader, $tableRows, 'compact');
    }
}
