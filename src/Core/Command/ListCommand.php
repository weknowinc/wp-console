<?php

/**
 * @file
 * Contains \WP\Console\Core\Command\ListCommand.
 */

namespace WP\Console\Core\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Core\Helper\DescriptorHelper;

/**
 * Class ListCommand
 * @package WP\Console\Core\Command
 */
class ListCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDefinition($this->createDefinition())
            ->setDescription($this->trans('commands.list.description'))
            ->setHelp($this->trans('commands.list.help'));
    }

    /**
     * {@inheritdoc}
     */
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        if ($input->getOption('xml')) {
            $io->info(
                'The --xml option was deprecated in version 2.7 and will be removed in version 3.0. Use the --format option instead',
                E_USER_DEPRECATED
            );
            $input->setOption('format', 'xml');
        }
        $helper = new DescriptorHelper();
        $helper->describe(
            $io,
            $this->getApplication(),
            [
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'namespace' => $input->getArgument('namespace'),
                'translator' => $this->getApplication()->getTranslator()
            ]
        );
    }


    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name'),
            new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
            new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
        ));
    }
}
