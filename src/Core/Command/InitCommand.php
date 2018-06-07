<?php

/**
 * @file
 * Contains \WP\Console\Core\Command\InitCommand.
 */

namespace WP\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Finder\Finder;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Generator\InitGenerator;
use WP\Console\Core\Utils\ShowFile;
use WP\Console\Core\Style\WPStyle;

/**
 * Class InitCommand
 *
 * @package WP\Console\Core\Command
 */
class InitCommand extends Command
{

    /**
     * @var ShowFile
     */
    protected $showFile;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var InitGenerator
     */
    protected $generator;

    private $configParameters = [
        'language' => 'en_GB',
        'temp' => '/tmp',
        'learning' => false,
        'generate_inline' => false,
        'generate_chain' => false,
        'statistics' => false
    ];

    /**
     * InitCommand constructor.
     *
     * @param ShowFile             $showFile
     * @param ConfigurationManager $configurationManager
     * @param InitGenerator        $generator
     * @param string               $appRoot
     * @param string               $consoleRoot
     */
    public function __construct(
        ShowFile $showFile,
        ConfigurationManager $configurationManager,
        InitGenerator $generator,
        $appRoot,
        $consoleRoot = null
    ) {
        $this->showFile = $showFile;
        $this->configurationManager = $configurationManager;
        $this->generator = $generator;
        $this->appRoot = $appRoot;
        $this->consoleRoot = $consoleRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription($this->trans('commands.init.description'))
            ->addOption(
                'destination',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.init.options.destination')
            )
            ->addOption(
                'override',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.override')
            )
            ->addOption(
                'autocomplete',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.autocomplete')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $destination = $input->getOption('destination');
        $autocomplete = $input->getOption('autocomplete');

        $configuration = $this->configurationManager->getConfiguration();
        $configurationDirectories = $this->configurationManager->getConfigurationDirectories();
        $applicationDirectory = $this->configurationManager->getApplicationDirectory();
        $configurationDirectories = array_filter(
            $configurationDirectories, function ($directory) use ($applicationDirectory) {
                return $directory != $applicationDirectory;
            }
        );


        if (!$destination) {
            if ($this->appRoot && $this->consoleRoot) {
                $destination = $io->choice(
                    $this->trans('commands.init.questions.destination'),
                    array_reverse($configurationDirectories)
                );
            } else {
                $destination = $this->configurationManager
                    ->getConsoleDirectory();
            }

            $input->setOption('destination', $destination);
        }

        $this->configParameters['language'] = $io->choiceNoList(
            $this->trans('commands.init.questions.language'),
            $configuration->get('application.languages')
        );

        $this->configParameters['temp'] = $io->ask(
            $this->trans('commands.init.questions.temp'),
            '/tmp'
        );

        $this->configParameters['learning'] = $io->confirm(
            $this->trans('commands.init.questions.learning'),
            true
        );

        $this->configParameters['generate_inline'] = $io->confirm(
            $this->trans('commands.init.questions.generate-inline'),
            false
        );

        $this->configParameters['generate_chain'] = $io->confirm(
            $this->trans('commands.init.questions.generate-chain'),
            false
        );

        if (!$autocomplete) {
            $autocomplete = $io->confirm(
                $this->trans('commands.init.questions.autocomplete'),
                false
            );
            $input->setOption('autocomplete', $autocomplete);
        }

        $io->commentBlock(
            sprintf(
                $this->trans('commands.init.messages.statistics'),
                sprintf(
                    '%sconfig.yml',
                    $this->configurationManager->getConsoleDirectory()
                )
            )
        );

        $this->configParameters['statistics'] = $io->confirm(
            $this->trans('commands.init.questions.statistics'),
            true
        );

        if ($this->configParameters['statistics']) {
            $io->commentBlock(
                $this->trans('commands.init.messages.statistics-disable')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $copiedFiles = [];
        $destination = $input->getOption('destination');
        $autocomplete = $input->getOption('autocomplete');
        $override = $input->getOption('override');
        if (!$destination) {
            $destination = $this->configurationManager->getConsoleDirectory();
        }

        $finder = new Finder();
        $finder->in(
            sprintf(
                '%s/config/dist/',
                $this->configurationManager->getApplicationDirectory()
            )
        );
        $finder->files();

        foreach ($finder as $configFile) {
            $sourceFile = sprintf(
                '%s/config/dist/%s',
                $this->configurationManager->getApplicationDirectory(),
                $configFile->getRelativePathname()
            );

            $destinationFile = sprintf(
                '%s%s',
                $destination,
                $configFile->getRelativePathname()
            );

            if ($this->copyFile($sourceFile, $destinationFile, $override)) {
                $copiedFiles[] = $destinationFile;
            }
        }

        if ($copiedFiles) {
            $this->showFile->copiedFiles($io, $copiedFiles, false);
            $io->newLine();
        }

        $executableName = null;
        if ($autocomplete) {
            $processBuilder = new ProcessBuilder(array('bash'));
            $process = $processBuilder->getProcess();
            $process->setCommandLine('echo $_');
            $process->run();
            $fullPathExecutable = explode('/', $process->getOutput());
            $executableName = trim(end($fullPathExecutable));
            $process->stop();
        }

        $this->generator->generate(
            $this->configurationManager->getConsoleDirectory(),
            $executableName,
            $override,
            $destination,
            $this->configParameters
        );

        $io->writeln($this->trans('application.messages.autocomplete'));

        return 0;
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $override
     * @return bool
     */
    private function copyFile($source, $destination, $override)
    {
        if (file_exists($destination)) {
            if ($override) {
                copy(
                    $destination,
                    $destination . '.old'
                );
            } else {
                return false;
            }
        }

        $filePath = dirname($destination);
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }

        return copy(
            $source,
            $destination
        );
    }
}
