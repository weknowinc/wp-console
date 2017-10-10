<?php

/**
 * @file
 * Contains \WP\Console\Command\Debug\ContainerCommand.
 */

namespace WP\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use WP\Console\Core\Command\ContainerAwareCommand;
use WP\Console\Core\Style\WPStyle;

/**
 * Class ContainerDebugCommand
 *
 * @package WP\Console\Command
 */
class ContainerCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:container')
            ->setDescription($this->trans('commands.debug.container.description'))
            ->addOption(
                'parameters',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.container.debug.arguments.service')
            )
            ->addArgument(
                'service',
                InputArgument::OPTIONAL,
                $this->trans('commands.container.debug.arguments.service')
            )
            ->setAliases(['dco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $service = $input->getArgument('service');
        $parameters = $input->getOption('parameters');

        if ($parameters) {
            $parameterList = $this->getParameterList();
            ksort($parameterList);
            $io->write(Yaml::dump(['parameters' => $parameterList], 4, 2));

            return 0;
        }

        $tableHeader = [];
        if ($service) {
            $tableRows = $this->getServiceDetail($service);
            $io->table($tableHeader, $tableRows, 'compact');

            return 0;
        }

        $tableHeader = [
            $this->trans('commands.debug.container.messages.service_id'),
            $this->trans('commands.debug.container.messages.class_name')
        ];

        $tableRows = $this->getServiceList();
        $io->table($tableHeader, $tableRows, 'compact');

        return 0;
    }

    private function getServiceList()
    {
        $services = [];
        $serviceDefinitions = $this->container
            ->getParameter('console.service_definitions');

        foreach ($serviceDefinitions as $serviceId => $serviceDefinition) {
            $services[] = [$serviceId, $serviceDefinition->getClass()];
        }
        return $services;
    }

    private function getServiceDetail($service)
    {
        $serviceInstance = $this->get($service);
        $serviceDetail = [];

        if ($serviceInstance) {
            $serviceDetail[] = [
                $this->trans('commands.debug.container.messages.service'),
                $service
            ];
            $serviceDetail[] = [
                $this->trans('commands.debug.container.messages.class'),
                get_class($serviceInstance)
            ];
            $serviceDetail[] = [
                $this->trans('commands.debug.container.messages.interface'),
                Yaml::dump(class_implements($serviceInstance))
            ];
            if ($parent = get_parent_class($serviceInstance)) {
                $serviceDetail[] = [
                    $this->trans('commands.debug.container.messages.parent'),
                    $parent
                ];
            }
            if ($vars = get_class_vars($serviceInstance)) {
                $serviceDetail[] = [
                    $this->trans('commands.debug.container.messages.variables'),
                    Yaml::dump($vars)
                ];
            }
            if ($methods = get_class_methods($serviceInstance)) {
                $serviceDetail[] = [
                    $this->trans('commands.debug.container.messages.methods'),
                    Yaml::dump($methods)
                ];
            }
        }

        return $serviceDetail;
    }

    private function getParameterList()
    {
        $parameters = array_filter(
            $this->container->getParameterBag()->all(), function ($name) {
                if (preg_match('/^container\./', $name)) {
                    return false;
                }
                if (preg_match('/^drupal\./', $name)) {
                    return false;
                }
                if (preg_match('/^console\./', $name)) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_KEY
        );

        return $parameters;
    }
}
