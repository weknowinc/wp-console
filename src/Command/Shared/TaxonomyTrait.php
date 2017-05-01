<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\TaxonomyTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class TaxonomyTrait
 *
 * @package WP\Console\Command
 */
trait TaxonomyTrait
{
    /**
     * @param \WP\Console\Core\Style\WPStyle $io
     * @return string
     * @throws \Exception
     */
    public function labelsQuestion(WPStyle $io)
    {
        $labels = array(
            'menu_name', 'all_items', 'parent_item', 'parent_item_colon', 'new_item_name', 'add_new_item',
            'edit_item', 'update_item', 'view_item', 'separate_items_with_commas', 'add_or_remove_items',
            'choose_from_most_used', 'popular_items', 'search_items', 'not_found', 'no_terms', 'items_list',
            'items_list_navigation'
        );
        
        $stringUtils = $this->stringConverter;
        $label_array = [];
        foreach ($labels as $label) {
            if ($io->confirm(
                $this->trans('commands.generate.taxonomy.questions.labels-add'). $label,
                true
            )
            ) {
                $result = $io->ask(
                    $this->trans('commands.generate.taxonomy.questions.labels-edit'). $label,
                    $stringUtils->camelCaseToHuman($label)
                );
                
                $label_array[$label] = $result;
            }
        }
        
        return $label_array;
    }
    
    public function postTypeQuestion()
    {
    }
    
    public function visibilityQuestion(WPStyle $io)
    {
        $visibility_public = $io->confirm(
            $this->trans('commands.generate.taxonomy.questions.visibility-public'),
            true
        );
        
        $visibility_show_ui = $io->confirm(
            $this->trans('commands.generate.taxonomy.questions.visibility-show-ui'),
            true
        );
        
        $visibility_show_admin_column = $io->confirm(
            $this->trans('commands.generate.taxonomy.questions.visibility-show-admin-column'),
            true
        );
        
        $visibility_show_in_nav_menus = $io->confirm(
            $this->trans('commands.generate.taxonomy.questions.visibility-show-in-nav-menus'),
            true
        );
        
        $visibility_show_tagcloud = $io->confirm(
            $this->trans('commands.generate.taxonomy.questions.visibility_show_tagcloud'),
            true
        );
        
        $visibility =
            [
                'public' => $visibility_public,
                'show_ui' => $visibility_show_ui,
                'show_admin_column' => $visibility_show_admin_column,
                'show_in_nav_menus' => $visibility_show_in_nav_menus,
                'show_tagcloud' => $visibility_show_tagcloud
            ];
        
        return $visibility;
    }
    
    public function permalinksQuestion(WPStyle $io)
    {
        $permalinks_url_slug = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.permalinks-url-slug'),
            'taxonomy'
        );
        
        $permalinks_use_url_slug = $io->confirm(
            $this->trans('commands.generate.taxonomy.questions.permalinks-use-url-slug'),
            true
        );
        
        $permalinks_hierarchical_url_slug = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.permalinks-hierarchical-url-slug'),
            false
        );
        
        $permalinks =
            [
                'slug' => $permalinks_url_slug,
                'with_front' => $permalinks_use_url_slug,
                'hierarchical' => $permalinks_hierarchical_url_slug,
            ];
        
        return $permalinks;
    }
    
    public function capabilitiesQuestion(WPStyle $io)
    {
        $capabilities_edit_terms = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.capabilities-edit-terms'),
            'manage_categories'
        );
        
        $capabilities_delete_terms = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.capabilities-delete-terms'),
            'manage_categories'
        );
        
        $capabilities_manage_terms = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.capabilities-manage-terms'),
            'manage_categories'
        );
        
        $capabilities_assign_terms = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.capabilities-assign-terms'),
            'edit_posts'
        );
        
        $capabilities =
            [
                'edit_terms' => $capabilities_edit_terms,
                'delete_terms' => $capabilities_delete_terms,
                'manage_terms' => $capabilities_manage_terms,
                'assign_terms' => $capabilities_assign_terms
            ];
        
        return $capabilities;
    }
    
    public function restQuestion(WPStyle $io)
    {
        $option_show_rest = ['no include', 'Yes', 'No'];
        $show_rest = $io->choiceNoList(
            $this->trans('commands.generate.taxonomy.questions.show-rest'),
            $option_show_rest
        );
        
        $rest_base = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.rest-base'),
            ''
        );
        
        $rest_controller_class = $io->ask(
            $this->trans('commands.generate.taxonomy.questions.rest-controller-class'),
            'WP_REST_Terms_Controller'
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
