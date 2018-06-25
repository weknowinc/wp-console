<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\FieldsTypeTrait.
 */

namespace WP\Console\Command\Shared;

/**
 * Class FieldsTypeTrait
 *
 * @package WP\Console\Command
 */
trait FieldsTypeTrait
{
    public function fieldsQuestion($command, $optionName, $sections = null)
    {
        $stringConverter = $this->stringConverter;

        $fields = [];
        $fields_options = ['text', 'number', 'email', 'url', 'password', 'text area', 'radio', 'select' ,'checkbox', 'date',
            'tel', 'color', 'oEmbed', 'file', 'image', 'month', 'search', 'submit', 'time', 'week'];
        $count = 0;
        while (true) {
            $type = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.type'),
                $fields_options,
                '',
                true
            );

            if (empty($type)) {
                break;
            }

            $label = $this->getIo()->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.label'),
                null
            );

            $id = $this->getIo()->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.id'),
                $this->stringConverter->createMachineName($label),
                function ($id) use ($stringConverter) {
                    return $stringConverter->createMachineName($id);
                }
            );

            $description = $this->getIo()->askEmpty(
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
                $placeholder = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.placeholder')
                );

                $default_value = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.default-value')
                );

                $fields[$count]['placeholder'] = $placeholder;
                $fields[$count]['default_value'] = $default_value;
            }

            if ($type == 'select' || $type == 'radio') {
                $fields[$count]['multi_selection'] =  $this->multiSelection($type, $command, $optionName);
            }

            if ($type == 'image') {
                $src = $this->getIo()->ask(
                    $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.src')
                );
                $fields[$count]['src_image'] =  $src;
            }

            if ($command == 'settings.page') {
                $section_id = $this->getIo()->choice(
                    $this->trans('commands.generate.settings.page.questions.fields.section-id'),
                    array_values($sections)
                );
                $fields[$count]['section_id'] = array_search($section_id, $sections);
            }

            if (!$this->getIo()->confirm(
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

    private function multiSelection($type, $command, $optionName)
    {
        $multiple_options = [];
        while (true) {
            $multiple_options_label = $this->getIo()->ask(
                $this->trans('commands.generate.'.$command.'.questions.'.$optionName.'.multiple-label').$type,
                ''
            );


            $multiple_options_value = $this->getIo()->ask(
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
            if (!$this->getIo()->confirm(
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
