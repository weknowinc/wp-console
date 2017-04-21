<?php

/**
 * @file
 * Contains WP\Console\Core\Command\Shared\MetaboxTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class MetaboxTrait
 *
 * @package Drupal\Console\Core\Command
 */
trait MetaboxTrait
{
    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function fieldMetaboxQuestion(WPStyle $io)
    {
        $validators = $this->validator;
        $stringConverter = $this->stringConverter;
        
        $fields = [];
        while (true) {
            $type = $io->choiceNoList(
                $this->trans('commands.generate.metabox.questions.field-type'),
                $this->arrayFieldType(),
                ''
            );
            
            
            $id = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-id'),
                '',
                function ($id) use ($stringConverter) {
                    return $stringConverter->camelCaseToUnderscore($id);
                }
            );
            
            $label = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-label'),
                ''
            );
            
            $description = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-description'),
                ''
            );
            
            $field_placeholder = '';
            $default_value = '';
            if($type != 'select' && $type != 'radio'){
                $field_placeholder = $io->ask(
                    $this->trans('commands.generate.metabox.questions.field-placeholder'),
                    ''
                );
                
                $default_value = $io->ask(
                    $this->trans('commands.generate.metabox.questions.field-default-value'),
                    ''
                );
            }
            
            $multi_selection = [];
            if($type == 'select' || $type == 'radio'){
                if (!$io->confirm(
                    $this->trans('commands.generate.metabox.questions.field-metabox-multiple-options', $type),
                    true
                )
                ) {
                    break;
                }
                $multi_selection = $this->multiSelection($io, $type);
                
            }
            
            array_push(
                $fields,
                [
                    'type' => $type,
                    'id' => $id,
                    'label' => $label,
                    'description' => $description,
                    'placeholder' => $field_placeholder,
                    'default_value' => $default_value,
                    'multiSelection' => $multi_selection
                ]
            );
            
            if (!$io->confirm(
                $this->trans('commands.generate.metabox.questions.field-metabox-add'),
                true
            )
            ) {
                break;
            }
        }
        
        return $fields;
    }
    
    private function multiSelection(WPStyle $io, $type){
        $multiple_options = [];
        while (true) {
            $multiple_options_label = $io->ask(
                $this->trans('commands.generate.metabox.questions.multiple-options-label'),
                ''
            );
            
            
            $multiple_options_value = $io->ask(
                $this->trans('commands.generate.metabox.questions.multiple-options-value'),
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
                $this->trans('commands.generate.metabox.questions.field-metabox-multiple-options-add', $type),
                true
            )
            ) {
                break;
            }
        }
        
        return $multiple_options;
    }
    
    private function arrayFieldType(){
        return ['select' ,'checkbox', 'color', 'date', 'email', 'file', 'image', 'month', 'number',
            'radio','search', 'submit', 'tel', 'text', 'time', 'url', 'week'];
    }
}
