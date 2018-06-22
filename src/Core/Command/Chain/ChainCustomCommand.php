<?php

/**
 * @file
 * Contains WP\Console\Core\Command\ChainCustomCommand.
 */

namespace WP\Console\Core\Command\Chain;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Core\Command\Shared\InputTrait;

/**
 * Class ChainCustomCommand
 *
 * @package WP\Console\Core\Command\ChainRegister
 */
class ChainCustomCommand extends BaseCommand
{
    use InputTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $file;

    /**
     * ChainRegister constructor.
     *
     * @param $name
     * @param $description
     * @param $file
     * @param $chainDiscovery
     */
    public function __construct(
        $name,
        $description,
        $file,
        $chainDiscovery
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->file = $file;

        parent::__construct($chainDiscovery);
        $this->ignoreValidationErrors();

        $this->addOption(
            'file',
            null,
            InputOption::VALUE_OPTIONAL,
            null,
            $file
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('chain');

        $arguments = [
            'command' => 'chain',
            '--file'  => $this->file,
        ];

        foreach ($input->getOptions() as $option => $value) {
            if ($value) {
                $arguments['--' . $option] = $value;
            }
        }

        $commandInput = new ArrayInput($arguments);
        $commandInput->setInteractive(true);

        return $command->run($commandInput, $output);
    }
}
