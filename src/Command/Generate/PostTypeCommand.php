<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\PostTypeCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\Taxonomy_PostTypeTrait;
use WP\Console\Extension\Manager;
use WP\Console\Generator\PostTypeGenerator;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Utils\StringConverter;

class PostTypeCommand extends Command
{
    use PluginTrait;
    use ConfirmationTrait;
    use CommandTrait;
    use Taxonomy_PostTypeTrait;

    /**
     * @var PostTypeGenerator
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
     * @param PostTypeGenerator $generator
     * @param Manager           $extensionManager
     * @param Validator         $validator
     * @param StringConverter   $stringConverter
     * @param Site              $site
     */
    public function __construct(
        PostTypeGenerator $generator,
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
            ->setName('generate:post:type')
            ->setDescription($this->trans('commands.generate.posttype.description'))
            ->setHelp($this->trans('commands.generate.posttype.help'))
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
                $this->trans('commands.generate.posttype.options.class-name')
            )
            ->addOption(
                'function-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.function-name')
            )
            ->addOption(
                'post-type-key',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.post-type-key')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.description')
            )
            ->addOption(
                'singular-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.singular-name')
            )
            ->addOption(
                'plural-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.plural-name')
            )
            ->addOption(
                'taxonomy',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.taxonomy')
            )
            ->addOption(
                'hierarchical',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.hierarchical')
            )
            ->addOption(
                'labels',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.labels')
            )
            ->addOption(
                'supports',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.supports')
            )
            ->addOption(
                'exclude-from-search',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.exclude-from-search')
            )
            ->addOption(
                'enable-export',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.enable-export')
            )
            ->addOption(
                'enable-archives',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.enable-archives')
            )
            ->addOption(
                'visibility',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.posttype.options.visibility')
            )
            ->addOption(
                'permalinks',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.posttype.options.permalinks')
            )
            ->addOption(
                'capabilities',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.posttype.options.capabilities')
            )
            ->addOption(
                'rest',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.posttype.options.rest')
            )
            ->addOption(
                'child-themes',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.posttype.options.child-themes')
            )
            ->setAliases(['gp']);
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
        $post_type_key = $input->getOption('post-type-key');
        $description = $input->getOption('description');
        $singular_name = $input->getOption('singular-name');
        $plural_name = $input->getOption('plural-name');
        $post_type = $input->getOption('taxonomy');
        $hierarchical = $input->getOption('hierarchical');
        $labels = $input->getOption('labels');
        $supports = $input->getOption('supports');
        $exclude_from_search = $input->getOption('exclude-from-search');
        $enable_export = $input->getOption('enable-export');
        $enable_archives = $input->getOption('enable-archives');
        $visibility = $input->getOption('visibility');
        $permalinks = $input->getOption('permalinks');
        $capabilities = $input->getOption('capabilities');
        $rest = $input->getOption('rest');
        $child_themes = $input->getOption('child-themes');

        $this->generator->generate(
            $plugin,
            $class_name,
            $function_name,
            $post_type_key,
            $description,
            $singular_name,
            $plural_name,
            $post_type,
            $hierarchical,
            $labels,
            $supports,
            $exclude_from_search,
            $enable_export,
            $enable_archives,
            $visibility,
            $permalinks,
            $capabilities,
            $rest,
            $child_themes
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
                $this->trans('commands.generate.posttype.questions.class-name'),
                $stringUtils->humanToCamelCase($plugin).'PostType',
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
                $this->trans('commands.generate.posttype.questions.function-name'),
                $stringUtils->camelCaseToUnderscore($class_name)
            );
            $input->setOption('function-name', $function_name);
        }

        // --post type key
        $post_type_key = $input->getOption('post-type-key');
        if (!$post_type_key) {
            $post_type_key = $io->ask(
                $this->trans('commands.generate.posttype.questions.post-type-key'),
                'post_type'
            );
            $post_type_key = $stringUtils->humanToCamelCase($post_type_key);
            $input->setOption('post-type-key', $post_type_key);
        }

        // --description
        $description = $input->getOption('description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.posttype.questions.description'),
                'Post Type Description'
            );
            $input->setOption('description', $description);
        }

        // --singular name
        $singular_name = $input->getOption('singular-name');
        if (!$singular_name) {
            $singular_name = $io->ask(
                $this->trans('commands.generate.posttype.questions.singular-name'),
                'post type'
            );
            $input->setOption('singular-name', $singular_name);
        }

        // --plural name
        $plural_name = $input->getOption('plural-name');
        if (!$plural_name) {
            $plural_name = $io->ask(
                $this->trans('commands.generate.posttype.questions.plural-name'),
                'post types'
            );
            $input->setOption('plural-name', $plural_name);
        }

        // --taxonomy
        /*       $post_type = $input->getOption('post-type');
            if (!$post_type) {
                $post_type = $io->ask(
                    $this->trans('commands.generate.posttype.questions.post-type'),
                    ['post', 'page']
                );
                $input->setOption('post-type', $post_type);
            }*/

        // --hierarchical
        $hierarchical = $input->getOption('hierarchical');
        if (!$hierarchical) {
            $hierarchical = $io->confirm(
                $this->trans('commands.generate.posttype.questions.hierarchical'),
                true
            );
            $input->setOption('hierarchical', $hierarchical);
        }

        // --labels
        $labels = $input->getOption('labels');
        if (!$labels) {
            if ($io->confirm(
                $this->trans('commands.generate.posttype.questions.labels'),
                false
            )
            ) {
                $labels = array(
                    'menu_name', 'name_admin_bar', 'archives', 'attributes', 'parent_item_colon', 'all_items',
                    'add_new_item', 'add_new', 'new_item', 'edit_item', 'update_item', 'view_item', 'view_items',
                    'search_items', 'not_found', 'not_found_in_trash','featured_image', 'set_featured_image',
                    'remove_featured_image', 'use_featured_image', 'insert_into_item', 'uploaded_to_this_item', 'items_list',
                    'items_list_navigation', 'filter_items_list'
                );
                // @see \WP\Console\Command\Shared\posttypeTrait::labelsQuestion
                $labels = $this->labelsQuestion($io, $labels);
                $input->setOption('labels', $labels);
            }
        }

        // --supports
        $supports = $input->getOption('supports');
        if (!$supports) {
            if ($io->confirm(
                $this->trans('commands.generate.posttype.questions.supports'),
                false
            )
            ) {
                $supports_labels = [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments',
                    'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats' ];

                foreach ($supports_labels as $label) {
                    if ($io->confirm(
                        $this->trans('commands.generate.posttype.questions.supports-edit'). $label,
                        false
                    )) {
                        $supports [] = $label;
                    }

                }
            }

            $input->setOption('supports', $supports);
        }

        // --exclude from search
        $exclude_from_search = $input->getOption('exclude-from-search');
        if (!$exclude_from_search) {
            $exclude_from_search = $io->confirm(
                $this->trans('commands.generate.posttype.questions.exclude-from-search'),
                false
            );
            $input->setOption('exclude-from-search', $exclude_from_search);
        }

        // --enable export
        $enable_export = $input->getOption('enable-export');
        if (!$enable_export) {
            $enable_export = $io->confirm(
                $this->trans('commands.generate.posttype.questions.enable-export'),
                false
            );
            $input->setOption('enable-export', $enable_export);
        }

        // --enable archives
        $enable_archives = $input->getOption('enable-archives');
        if (!$enable_archives) {
            $enable_archives = $io->choice(
                $this->trans('commands.generate.posttype.questions.enable-archives'),
                ['true', 'false', 'Custom']
            );
            if($enable_archives == 'Custom')
            {
                $enable_archives = $io->ask($this->trans('commands.generate.posttype.questions.enable-archives-custom'));

            } else { $enable_archives = settype($enable_archives,'boolean');}

            $input->setOption('enable-archives', $enable_archives);
        }

        // --visibility
        $visibility = $input->getOption('visibility');
        if (!$visibility) {
            $visibility = [
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_position' => 5,
                'show_in_admin_bar' => true,
                'show_in_nav_menus' => true
            ];

            if ($io->confirm(
                $this->trans('commands.generate.posttype.questions.visibility'),
                false
            )
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::visibilityQuestion
                $visibility = $this->visibilityQuestion($io, $visibility);
            }

            $input->setOption('visibility', $visibility);
        }

        // --permalinks
        $options_permalinks= ['default', 'custom', 'no permalinks'];
        $permalinks = $input->getOption('permalinks');
        if (!$permalinks) {
            if ($io->choice(
                    $this->trans('commands.generate.posttype.questions.permalinks'),
                    $options_permalinks
                ) == 'custom'
            ) {
                $permalinks_labels = [ 'slug', 'with_front', 'pages', 'feeds' ];

                // @see \WP\Console\Command\Shared\TaxonomyTrait::permalinksQuestion
                $permalinks = $this->permalinksQuestion($io, $permalinks_labels);
                $input->setOption('permalinks', $permalinks);
            }
        }

        // --capabilities
        $capabilities = $input->getOption('capabilities');
        if (!$capabilities) {

            if ($io->confirm(
                $this->trans('commands.generate.posttype.questions.capabilities'),
                false
            )
            ) {

                $capabilities_labels = ['edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
                    'publish_posts', 'read_private_posts'];

                // @see \WP\Console\Command\Shared\TaxonomyTrait::capabilitiesQuestion
                $capabilities = $this->capabilitiesQuestion($io, $capabilities_labels);
            } else {
                $capabilities = $io->choice(
                    $this->trans('commands.generate.posttype.questions.capabilities'),
                    ['page', 'post']
                );
            }
            $input->setOption('capabilities', $capabilities);
        }

        // --rest
        $rest = $input->getOption('rest');
        if (!$rest) {
            if ($io->confirm(
                $this->trans('commands.generate.posttype.questions.rest'),
                false
            )
            ) {
                // @see \WP\Console\Command\Shared\TaxonomyTrait::restQuestion
                $rest = $this->restQuestion($io, 'Post', $input->getOption('post-type-key'));
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
    }
}
