<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\FieldsTypeTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class FieldsTypeTrait
 *
 * @package WP\Console\Command
 */
trait FieldsTypeTrait
{

    public function fieldsQuestion(WPStyle $io, $command, $optionName, $sections = null)
    {
        $stringConverter = $this->stringConverter;

        $fields = [];
        $fields_options = ['text', 'number', 'email', 'url', 'password', 'text area', 'radio', 'select' ,'checkbox', 'date',
            'tel', 'color', 'oEmbed', 'file', 'image', 'month', 'search', 'submit', 'time', 'week'];
        $count = 0;
        while (true) {
            $type = $io->choiceNoList(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.type'),
                $fields_options,
                NULL,
                TRUE
            );

            if (empty($type)) {
                break;
            }

            $label = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.label'),
                null
            );

            $id = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.id'),
                $this->stringConverter->createMachineName($label),
                function ($id) use ($stringConverter) {
                    return $stringConverter->createMachineName($id);
                }
            );

            $description = $io->askEmpty(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.description')
            );

            array_push(
                $fields,
                [
                    'type' => $type,
                    'id' => $id,
                    'label' => $label,
                    'description' => $description,
                ]
            );

            if ($type != 'select' && $type != 'radio' && $type != 'checkbox') {
                $placeholder = $io->askEmpty(
                    $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.placeholder')
                );

                $default_value = $io->askEmpty(
                    $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.default-value')
                );

                $fields[$count]['placeholder'] = $placeholder;
                $fields[$count]['default_value'] = $default_value;
            }

            if ($type == 'select' || $type == 'radio') {
                $fields[$count]['multi_selection'] =  $this->multiSelection($io, $type, $command, $optionName);
            }

            if ($type == 'image') {
                $src = $io->ask(
                    $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.src')
                );
                $fields[$count]['src_image'] =  $src;
            }

            if ($command == 'settings.page') {
                $section_id = $io->choice(
                    $this->trans('commands.generate.settings.page.questions.fields.section-id'),
                    array_values($sections)
                );
                $fields[$count]['section_id'] = array_search($section_id, $sections);
            }

            if (!$io->confirm(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.'.$optionName.'-add-another'),
                false
            )
            ) {
                break;
            }
            $count ++;
        }

        return $fields;
    }

    private function multiSelection(WPStyle $io, $type, $command, $optionName)
    {
        $multiple_options = [];
        while (true) {
            $multiple_options_label = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.multiple-label').$type,
                ''
            );


            $multiple_options_value = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.multiple-value').$type,
                ''
            );

            array_push(
                $multiple_options,
                [
                    'label' => $multiple_options_label,
                    'value' => $multiple_options_value
                ]
            );
            if (!$io->confirm(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.multiple-options-add').$type,
                false
            )
            ) {
                break;
            }
        }

        return $multiple_options;
    }
}
