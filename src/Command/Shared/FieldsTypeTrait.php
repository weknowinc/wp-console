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

    public function fieldsQuestion(WPStyle $io, $command)
    {
        $stringConverter = $this->stringConverter;

        $fields = [];
        $fields_options = ['text', 'number', 'email', 'url', 'password', 'text area', 'radio', 'select' ,'checkbox', 'date',
            'tel', 'color', 'oEmbed', 'file', 'image', 'month', 'search', 'submit', 'time', 'week'];
        $count = 0;
        while (true) {
            $type = $io->choiceNoList(
                $this->trans('commands.generate.'.$command.'.questions.fields.type'),
                $fields_options,
                NULL,
                TRUE
            );

            if (empty($type)) {
                break;
            }

            $label = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.fields.label'),
                null
            );

            $id = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.fields.id'),
                $this->stringConverter->createMachineName($label),
                function ($id) use ($stringConverter) {
                    return $stringConverter->createMachineName($id);
                }
            );

            $description = $io->askEmpty(
                $this->trans('commands.generate.'.$command.'.questions.fields.description')
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
                    $this->trans('commands.generate.'.$command.'.questions.fields.placeholder')
                );

                $default_value = $io->askEmpty(
                    $this->trans('commands.generate.'.$command.'.questions.fields.default-value')
                );

                $fields[$count]['placeholder'] = $placeholder;
                $fields[$count]['default_value'] = $default_value;
            }

            if ($type == 'select' || $type == 'radio' || $type == 'checkbox') {
                if ($io->confirm(
                    $this->trans('commands.generate.'.$command.'.questions.fields.multiple-options', $type),
                    false
                )
                ) {
                    $fields[$count]['multi_selection'] =  $this->multiSelection($io, $type, $command);
                }
            }

            if ($type == 'image') {
                $src = $io->ask(
                    $this->trans('commands.generate.'.$command.'.questions.fields.src')
                );
                $fields[$count]['src_image'] =  $src;
            }

            if (!$io->confirm(
                $this->trans('commands.generate.'.$command.'.questions.fields.generate-add'),
                false
            )
            ) {
                break;
            }
            $count ++;
        }

        return $fields;
    }

    private function multiSelection(WPStyle $io, $type, $command)
    {
        $multiple_options = [];
        while (true) {
            $multiple_options_label = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.fields.multiple-label'),
                ''
            );


            $multiple_options_value = $io->ask(
                $this->trans('commands.generate.'.$command.'.questions.fields.multiple-value'),
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
                $this->trans('commands.generate.'.$command.'.questions.fields.multiple-options-add', $type),
                false
            )
            ) {
                break;
            }
        }

        return $multiple_options;
    }
}
