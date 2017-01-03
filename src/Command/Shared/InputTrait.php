<?php

/**
 * @file
 * Contains WP\Console\Core\Command\Shared\InputTrait.
 */

namespace WP\Console\Command\Shared;

/**
 * Class InputTrait
 * @package Drupal\Console\Core\Command
 */
trait InputTrait
{
    /**
     * @return array
     */
    private function inlineValueAsArray($inputValue)
    {
        $inputArrayValue = [];
        foreach ($inputValue as $key => $value) {
            if (!is_array($value)) {
                $separatorIndex = strpos($value, ':');
                if (!$separatorIndex) {
                    continue;
                }
                $inputKeyItem = substr($value, 0, $separatorIndex);
                $inputValueItem = substr($value, $separatorIndex+1);
                $inputArrayValue[$key] = [$inputKeyItem => $inputValueItem];
            }
        }

        return $inputArrayValue?$inputArrayValue:$inputValue;
    }
}
