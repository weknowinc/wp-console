<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\TaxonomyCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\TaxonomyTrait;
use WP\Console\Extension\Manager;
use WP\Console\Generator\TaxonomyGenerator;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Utils\StringConverter;

class TaxonomyCommand extends Command
{
    use PluginTrait;
    use ConfirmationTrait;
    use CommandTrait;
    
    /**
     * @var TaxonomyGenerator
     */
    protected $generator;
    
    /**
     * @var Validator
     */
    protected $validator;
    
    /**
     * @var Manager
     */
    protected $extensionManager;
    
    /**
     * @var StringConverter
     */
    protected $stringConverter;
    
    /**
     * @var string
     */
    protected $twigtemplate;
    
    /**
     * @var Site
     */
    protected $site;
    
    
    /**
     * TaxonomyCommand constructor.
     *
     * @param TaxonomyGenerator $generator
     * @param Manager           $extensionManager
     * @param Validator         $validator
     * @param StringConverter   $stringConverter
     * @param Site              $site
     */
    public function __construct(
        TaxonomyGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        Site $site
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->site = $site;
        parent::__construct();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:taxonomy')
            ->setDescription($this->trans('commands.generate.taxonomy.description'))
            ->setHelp($this->trans('commands.generate.taxonomy.help'))
            ->addOption(
                'plugin',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.class-name')
            )
            ->addOption(
                'function-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.function-name')
            )
            ->addOption(
                'taxonomy-key',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.taxonomy-key')
            )
            ->addOption(
                'singular-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.singular-name')
            )
            ->addOption(
                'plural-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.plural-name')
            )
            ->addOption(
                'post-type',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.post-type')
            )
            ->addOption(
                'hierarchical',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.hierarchical')
            )
            ->addOption(
                'labels',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.screen')
            )
            ->addOption(
                'visibility',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.taxonomy.options.visibility')
            )
            ->addOption(
                'permalinks',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.taxonomy.options.permalinks')
            )
            ->addOption(
                'capabilities',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.taxonomy.options.capabilities')
            )
            ->addOption(
                'rest',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.taxonomy.options.rest')
            )
            ->addOption(
                'child-themes',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.taxonomy.options.child-themes')
            )
            ->addOption(
                'update-count-callback',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.taxonomy.options.update-count-callback')
            );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }
        
        $plugin = $plugin = $this->validator->validatePluginName($input->getOption('plugin'));
        $class_name = $input->getOption('class-name');
        $function_name = $input->getOption('function-name');
        $taxonomy_key = $input->getOption('taxonomy-key');
        $singular_name = $input->getOption('singular-name');
        $plural_name = $input->getOption('plural-name');
        $post_type = $input->getOption('post-type');
        $hierarchical = $input->getOption('hierarchical');
        $labels = $input->getOption('labels');
        $visibility = $input->getOption('visibility');
        $permalinks = $input->getOption('permalinks');
        $capabilities = $input->getOption('capabilities');
        $rest = $input->getOption('rest');
        $child_themes = $input->getOption('child-themes');
        $update_count_callback = $input->getOption('update-count-callback');
        
        $this->generator->generate(
            $plugin,
            $class_name,
            $function_name,
            $taxonomy_key,
            $singular_name,
            $plural_name,
            $post_type,
            $hierarchical,
            $labels,
            $visibility,
            $permalinks,
            $capabilities,
            $rest,
            $child_themes,
            $update_count_callback
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        
        $validator = $this->validator;
        $stringUtils = $this->stringConverter;
        
        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io);
            $input->setOption('plugin', $plugin);
        }
        
        // --class name
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.taxonomy.questions.class-name'),
                $stringUtils->humanToCamelCase($plugin).'Taxonomy',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class-name', $class_name);
        }
        
        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $io->ask(
                $this->trans('commands.generate.taxonomy.questions.function-name'),
                $class_name.'_taxonomy'
            );
            $input->setOption('function-name', $function_name);
        }
        
        // --taxonomy key
        $taxonomy_key = $input->getOption('taxonomy-key');
        if (!$taxonomy_key) {
            $taxonomy_key = $io->ask(
                $this->trans('commands.generate.taxonomy.questions.taxonomy_key'),
                'taxonomy'
            );
            $taxonomy_key = $stringUtils->humanToCamelCase($taxonomy_key);
            $input->setOption('taxonomy-key', $taxonomy_key);
        }
        
        // --singular name
        $singular_name = $input->getOption('singular-name');
        if (!$singular_name) {
            $singular_name = $io->ask(
                $this->trans('commands.generate.taxonomy.questions.singular-name'),
                'Taxonomy'
            );
            $input->setOption('singular-name', $singular_name);
        }
        
        // --plural name
        $plural_name = $input->getOption('plural-name');
        if (!$plural_name) {
            $plural_name = $io->ask(
                $this->trans('commands.generate.taxonomy.questions.plural-name'),
                'Taxonomies'
            );
            $input->setOption('plural-name', $plural_name);
        }
        
        // --post type
        /*       $post_type = $input->getOption('post-type');
            if (!$post_type) {
                $post_type = $io->ask(
                    $this->trans('commands.generate.taxonomy.questions.post-type'),
                    ['post', 'page']
                );
                $input->setOption('post-type', $post_type);
            }*/
        
        // --hierarchical
        $hierarchical = $input->getOption('hierarchical');
        if (!$hierarchical) {
            $hierarchical = $io->confirm(
                $this->trans('commands.generate.taxonomy.questions.hierarchical'),
                true
            );
            $input->setOption('hierarchical', $hierarchical);
        }
        
        // --labels
           $labels = $input->getOption('labels');
        if (!$labels) {
            if ($io->confirm(
                $this->trans('commands.generate.taxonomy.questions.labels'),
                false
            )
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::labelsQuestion
                    $labels = $this->labelsQuestion($io);
                $input->setOption('labels', $labels);
            }
        }
        
        // --visibility
        $visibility = $input->getOption('visibility');
        if (!$visibility) {
            if ($io->confirm(
                $this->trans('commands.generate.taxonomy.questions.visibility'),
                false
            )
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::visibilityQuestion
                $visibility = $this->visibilityQuestion($io);
            } else {
                $visibility = [
                    'public' => true,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'show_in_nav_menus' => true,
                    'show_tagcloud' => true
                ];
            }
            $input->setOption('visibility', $visibility);
        }
        
        // --permalinks
        $options_permalinks= ['default', 'custom', 'no permalinks'];
        $permalinks = $input->getOption('permalinks');
        if (!$permalinks) {
            if ($io->choice(
                $this->trans('commands.generate.taxonomy.questions.permalinks'),
                $options_permalinks
            ) == 'custom'
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::permalinksQuestion
                $permalinks = $this->permalinksQuestion($io);
                $input->setOption('permalinks', $permalinks);
            }
        }
        
        // --capabilities
        $capabilities = $input->getOption('capabilities');
        if (!$capabilities) {
            if ($io->confirm(
                $this->trans('commands.generate.taxonomy.questions.capabilities'),
                false
            )
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::capabilitiesQuestion
                $capabilities = $this->capabilitiesQuestion($io);
                $input->setOption('capabilities', $capabilities);
            }
        }
        
        // --rest
        $rest = $input->getOption('rest');
        if (!$rest) {
            if ($io->confirm(
                $this->trans('commands.generate.taxonomy.questions.rest'),
                false
            )
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::restQuestion
                $rest = $this->restQuestion($io);
                $input->setOption('rest', $rest);
            }
        }
        
        // --child themes
        $child_themes = $input->getOption('child-themes');
        if (!$child_themes) {
            $child_themes = $io->confirm(
                $this->trans('commands.generate.taxonomy.questions.child-themes'),
                false
            );
            $input->setOption('child-themes', $child_themes);
        }
        
        // --update count callback
        $update_count_callback = $input->getOption('update-count-callback');
        if (!$update_count_callback) {
            if ($io->confirm(
                $this->trans('commands.generate.taxonomy.questions.update-count-callback-add'),
                false
            )
            ) {
                $update_count_callback = $io->ask($this->trans('commands.generate.taxonomy.questions.update-count-callback'));
                $update_count_callback = $stringUtils->humanToCamelCase($update_count_callback);
                $input->setOption('update-count-callback', $update_count_callback);
            }
        }
    }

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
