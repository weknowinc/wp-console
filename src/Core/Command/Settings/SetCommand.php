<?php

/**
 * @file
 * Contains \WP\Console\Core\Command\Settings\SetCommand.
 */

namespace WP\Console\Core\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Utils\NestedArray;

/**
 * Class SetCommand
 *
 * @package WP\Console\Core\Command\Settings
 */
class SetCommand extends Command
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var NestedArray
     */
    protected $nestedArray;

    /**
     * CheckCommand constructor.
     *
     * @param ConfigurationManager $configurationManager
     * @param NestedArray          $nestedArray
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        NestedArray $nestedArray
    ) {
        $this->configurationManager = $configurationManager;
        $this->nestedArray = $nestedArray;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('settings:set')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                $this->trans('commands.settings.set.arguments.name'),
                null
            )
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                $this->trans('commands.settings.set.arguments.value'),
                null
            )
            ->setDescription($this->trans('commands.settings.set.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $parser = new Parser();
        $dumper = new Dumper();

        $settingName = $input->getArgument('name');
        $settingValue = $input->getArgument('value');

        // Change the value type if it is boolean.
        if ($settingValue == 'true' || $settingValue == 'false') {
            $settingValue = filter_var($settingValue, FILTER_VALIDATE_BOOLEAN);
        }

        $userConfigFile = sprintf(
            '%s/.wp-console/config.yml',
            $this->configurationManager->getHomeDirectory()
        );

        if (!file_exists($userConfigFile)) {
            $io->error(
                sprintf(
                    $this->trans('commands.settings.set.messages.missing-file'),
                    $userConfigFile
                )
            );
            return 1;
        }

        try {
            $userConfigFileParsed = $parser->parse(
                file_get_contents($userConfigFile)
            );
        } catch (\Exception $e) {
            $io->error(
                $this->trans(
                    'commands.settings.set.messages.error-parsing'
                ) . ': ' . $e->getMessage()
            );
            return 1;
        }

        $parents = array_merge(['application'], explode(".", $settingName));

        $this->nestedArray->setValue(
            $userConfigFileParsed,
            $parents,
            $settingValue,
            true
        );

        try {
            $userConfigFileDump = $dumper->dump($userConfigFileParsed, 10);
        } catch (\Exception $e) {
            $io->error(
                [
                    $this->trans('commands.settings.set.messages.error-generating'),
                    $e->getMessage()
                ]
            );

            return 1;
        }

        if ($settingName == 'language') {
            $this->getApplication()
                ->getTranslator()
                ->changeCoreLanguage($settingValue);

            $translatorLanguage = $this->getApplication()->getTranslator()->getLanguage();
            if ($translatorLanguage != $settingValue) {
                $io->error(
                    sprintf(
                        $this->trans('commands.settings.set.messages.missing-language'),
                        $settingValue
                    )
                );

                return 1;
            }
        }

        try {
            file_put_contents($userConfigFile, $userConfigFileDump);
        } catch (\Exception $e) {
            $io->error(
                [
                    $this->trans('commands.settings.set.messages.error-writing'),
                    $e->getMessage()
                ]
            );

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.settings.set.messages.success'),
                $settingName,
                $settingValue
            )
        );

        return 0;
    }
}
