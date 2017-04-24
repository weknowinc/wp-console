<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\DebugCommand.
 */

namespace WP\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Extension\Manager;

class DebugCommand extends Command
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
     * @param Site                 $site
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
            ->setName('theme:debug')
            ->setDescription($this->trans('commands.theme.debug.description'))
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.theme.debug.options.status')
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        $status = $input->getOption('status');
        
        $tableHeader = [
            $this->trans('commands.theme.debug.messages.name'),
            $this->trans('commands.theme.debug.messages.status'),
            $this->trans('commands.theme.debug.messages.theme-uri'),
            $this->trans('commands.theme.debug.messages.version'),
            $this->trans('commands.theme.debug.messages.author'),
            $this->trans('commands.theme.debug.messages.author-url')
        ];
    
        $tableRows = [];
        
        $discoverThemes = $this->extensionManager->discoverThemes();
        if($status == 'disabled') {
            $discoverThemes->showDeactivated();
        } elseif($status == 'enabled') {
            $discoverThemes->showActivated();
        } else {
            $discoverThemes->showActivated()->showDeactivated();
        }
    
        $themes = $discoverThemes->getList();
       
        foreach ($themes as $theme => $themeData ) {
            $themeStatus = ($this->site->isThemeActive($theme))?$this->trans('commands.common.status.enabled'): $this->trans('commands.common.status.disabled');
            
            $tableRows [] = [
                $themeData->get('Name'),
                $themeStatus,
                $themeData->get('ThemeURI'),
                $themeData->get('Version'),
                $themeData->get('Author'),
                $themeData->get('AuthorURI'),
            ];
        }
        
        $io->table($tableHeader, $tableRows, 'compact');
    }
}
