<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\TaxonomyPostTypeTrait.
 */

namespace WP\Console\Command\Shared;

/**
 * Class TaxonomyPostTypeTrait
 *
 * @package WP\Console\Command
 */
trait TaxonomyPostTypeTrait
{
    public function labelsQuestion($labels, $translations)
    {
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($labels as $label) {
            if ($this->getIo()->confirm(
                $this->trans('commands.generate.'.$translations.'.questions.labels-add'). $label,
                true
            )
            ) {
                $value = $stringUtils->underscoreToCamelCase($label);
                $value = $stringUtils->camelCaseToHuman($value);
                $label_array[$label] = $this->getIo()->ask(
                    $this->trans('commands.generate.'.$translations.'.questions.labels-edit'). $label,
                    $value
                );
            }
        }

        return $label_array;
    }

    public function visibilityQuestion($labels_visibility, $translations)
    {
        foreach ($labels_visibility as $index => $value) {
            if ($index != 'menu_position') {
                $labels_visibility[$index] = ($this->getIo()->confirm(
                    $this->trans('commands.generate.'.$translations.'.questions.visibility-options'). str_replace('show_in_', '', $index),
                    true
                )) ? 'true' : 'false';
            }

            if ($index == 'show_in_menu' && $labels_visibility[$index]) {
                $array_menu_position = ['5' => 'below Post', '10' => 'below Post', '15' => 'below Links', '20' => 'below Pages',
                    '25' => 'below Comments', '60' => 'below First separator', '65' => 'below Plugins',
                    '70' => 'below Users', '75' => 'below Tools','80' => 'below Settings', '100' => 'below second separator' ];

                $result = $this->getIo()->choice(
                    $this->trans('commands.generate.'.$translations.'.questions.labels-edit'),
                    $array_menu_position
                );

                $labels_visibility['menu_position'] = array_search($result, $array_menu_position);
            }
        }

        return $labels_visibility;
    }

    public function permalinksQuestion($permalinks, $translations)
    {
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($permalinks as $permalink) {
            if ($permalink != 'slug') {
                $label_array[$permalink] = ($this->getIo()->confirm(
                    $this->trans('commands.generate.'.$translations.'.questions.permalinks-options'). $permalink,
                    true
                )) ? 'true' : 'false';
            } else {
                $value = $this->getIo()->ask(
                    $this->trans('commands.generate.'.$translations.'.questions.permalinks-slug'),
                    'post_type'
                );

                $label_array[$permalink] = $stringUtils->camelCaseToUnderscore($stringUtils->humanToCamelCase($value));
            }
        }

        return $label_array;
    }

    public function capabilitiesQuestion($capabilities_labels, $translations)
    {
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($capabilities_labels as $label) {
            $value = $this->getIo()->ask(
                $this->trans('commands.generate.'.$translations.'.questions.capabilities-options').$stringUtils->camelCaseToHuman($stringUtils->underscoreToCamelCase($label)),
                $label
            );

            $label_array[$label] = $stringUtils->camelCaseToUnderscore($stringUtils->humanToCamelCase($value));
        }
        return $label_array;
    }

    public function restQuestion($type, $key, $translations)
    {
        $show_rest = $this->getIo()->confirm(
            $this->trans('commands.generate.'.$translations.'.questions.show-rest')
        );

        $rest_base = $this->getIo()->ask(
            $this->trans('commands.generate.'.$translations.'.questions.rest-base'),
            $key
        );

        $rest_controller_class = $this->getIo()->ask(
            $this->trans('commands.generate.'.$translations.'.questions.rest-controller-class'),
            'WP_REST_'.$type.'_Controller'
        );

        $rests =
            [
                'show_in_rest' => ($show_rest) ? 'true' : 'false',
                'rest_base' => $rest_base,
                'rest_controller_class' => $rest_controller_class,
            ];

        return $rests;
    }
}
