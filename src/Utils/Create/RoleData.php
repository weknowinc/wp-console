<?php

/**
 * @file
 * Contains \WP\Console\Utils\Create\RoleData.
 */

namespace WP\Console\Utils\Create;

use Faker;
use WP\Console\Utils\Site;
use WP\Console\Utils\WordpressApi;

/**
 * Class Roles
 *
 * @package Drupal\Console\Utils\Create
 */
class RoleData
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var WordpressApi
     */
    protected $wordpressApi;

    /**
     * UserData constructor.
     *
     * @param Site         $site
     * @param WordpressApi $wordpressApi
     */
    public function __construct(
        Site $site,
        WordpressApi $wordpressApi
    ) {
        $this->site = $site;
        $this->wordpressApi = $wordpressApi;
    }

    /**
     * Create and returns an array of new Roles.
     *
     * @param $limit
     *
     * @return array
     */
    public function create(
        $limit
    ) {
        $faker = Faker\Factory::create();
        $roles = [];
        $this->site->loadLegacyFile('wp-includes/capabilities.php');

        for ($i = 0; $i < $limit; $i++) {
            $rolename = $faker->userName();

            try {
                $role = add_role(
                    $rolename,
                    $rolename,
                    [
                        "edit_posts" => true,
                        "read" => true,
                        "level_1" => true,
                        "level_0" => true,
                        "delete_posts" => true
                    ]
                );

                $roles['success'][] = [
                    'role-id' => $role->name,
                    'role-name' => $role->name
                ];
            } catch (\Exception $error) {
                $roles['error'][] = [
                    'error' => $error->getMessage()
                ];
            }
        }

        return $roles;
    }
}
