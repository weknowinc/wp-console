<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\Taxonomy_PostTypeTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class PluginTrait
 *
 * @package WP\Console\Command
 */
trait Taxonomy_PostTypeTrait
{
    public function labelsQuestion(WPStyle $io, $labels)
    {
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($labels as $label) {
            if ($io->confirm(
                $this->trans('commands.generate.posttype.questions.labels-add'). $label,
                true
            )
            ) {
                $value = $io->ask(
                    $this->trans('commands.generate.posttype.questions.labels-edit'). $label,
                    $stringUtils->underscoreToCamelCase($label)
                );

                $label_array[$label] = $stringUtils->camelCaseToUnderscore($stringUtils->humanToCamelCase($value));
            }
        }

        return $label_array;
    }

    public function visibilityQuestion(WPStyle $io, $labels_visibility)
    {
        foreach ($labels_visibility as $index => $value) {
            if ($index != 'menu_position') {
                $labels_visibility[$index] = $io->confirm(
                    $this->trans('commands.generate.posttype.questions.visibility-options'). str_replace('show_in_', '', $index),
                    true
                );
            }

            if ($index == 'show_in_menu' && $labels_visibility[$index]) {

                $array_menu_position = ['5' => 'below Post', '10' => 'below Post', '15' => 'below Links', '20' => 'below Pages',
                    '25' => 'below Comments', '60' => 'below First separator', '65' => 'below Plugins',
                    '70' => 'below Users', '75' => 'below Tools','80' => 'below Settings', '100' => 'below second separator' ];

                $result = $io->choice(
                    $this->trans('commands.generate.posttype.questions.labels-edit'),
                    $array_menu_position
                );

                $labels_visibility['menu_position'] = array_search($result, $array_menu_position);
            }
        }

        return $labels_visibility;
    }

    public function permalinksQuestion(WPStyle $io, $permalinks)
    {
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($permalinks as $permalink) {
            if ($permalink != 'slug') {
                $label_array[$permalink] = $io->confirm(
                    $this->trans('commands.generate.posttype.questions.permalinks-options'). $permalink,
                    true
                );
            } else {

                $value = $io->ask(
                    $this->trans('commands.generate.posttype.questions.permalinks-slug'),
                    'post_type'
                );

                $label_array[$permalink] = $stringUtils->camelCaseToUnderscore($stringUtils->humanToCamelCase($value));
            }
        }

        return $label_array;
    }

    public function capabilitiesQuestion(WPStyle $io, $capabilities_labels)
    {
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($capabilities_labels as $label) {

            $value = $io->ask(
                $this->trans('commands.generate.posttype.questions.capabilities-options').$stringUtils->camelCaseToHuman($stringUtils->underscoreToCamelCase($label)) ,
                $label
            );

            $label_array[$label] = $stringUtils->camelCaseToUnderscore($stringUtils->humanToCamelCase($value));
        }
        return $label_array;
    }

    public function restQuestion(WPStyle $io, $type, $key)
    {

        $show_rest = $io->confirm(
            $this->trans('commands.generate.posttype.questions.show-rest')
        );

        $rest_base = $io->ask(
            $this->trans('commands.generate.posttype.questions.rest-base'),
            $key
        );

        $rest_controller_class = $io->ask(
            $this->trans('commands.generate.posttype.questions.rest-controller-class'),
            'WP_REST_'.$type.'_Controller'
        );

        $rests =
            [
                'show_in_rest' => $show_rest,
                'rest_base' => $rest_base,
                'rest_controller_class' => $rest_controller_class,
            ];

        return $rests;
    }
}