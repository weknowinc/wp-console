<?php

/**
 * @file
 * Contains WP\Console\Core\Command\Shared\MetaboxTrait.
 */

namespace WP\Console\Command\Shared;

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
        $fields = [];
        while (true) {
            $type = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-type'),
                'Content'
            );
            
           
            $id = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-id')
            );
    
            $label = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-label')
            );
    
            $description = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-description')
            );
    
            $field_placeholder = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-placeholder')
            );
    
            $default_value = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-default-value')
            );
            
            array_push(
                $fields,
                [
                    'type' => $type,
                    'id' => $id,
                    'label' => $label,
                    'description' => $description,
                    'field_placeholder' => $field_placeholder,
                    'default_value' => $default_value,
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
}
