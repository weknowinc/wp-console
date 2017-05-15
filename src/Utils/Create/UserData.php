<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\UserData.
 */

namespace WP\Console\Utils\Create;

use Faker;
use Faker\Provider\Miscellaneous;
use Faker\Provider\Internet;
use WP\Console\Utils\Site;
/**
 * Class Users
 *
 * @package WP\Console\Utils\Create
 */
class UserData
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * UserData constructor.
     *
     * @param Site       $site
     */
    public function __construct(
        Site $site
    ) {
        $this->site = $site;
    }

    /**
     * Create and returns an array of new Users.
     *
     * @param $roles
     * @param $limit
     * @param $password
     * @param $timeRange
     *
     * @return array
     */
    public function create(
        $role,
        $limit,
        $password,
        $timeRange
    ) {

        $faker = Faker\Factory::create();
        $faker->password();

        $users = [];
        for ($i=0; $i<$limit; $i++) {

            try {
                $username = $faker->name;
                $userpass = $password?$password:$faker->password();
                $userID = wp_insert_user( array(
                    'user_login' => $faker->userName(),
                    'user_pass' => $userpass,
                    'user_email' => $faker->email,
                    'nickname' => $username,
                    'display_name' => $username,
                    'role' => $role,
                    'user_registered' => date('Y-m-d H:i:s', time() - mt_rand(0, $timeRange))
                    )
                );

                $user = $this->site->getUserBy('id', $userID);

                $users['success'][] = [
                    'user-id' => $user->ID,
                    'username' => $user->get('user_login'),
                    'password' => $userpass,
                    'role' => $user->roles[0],
                    'created' => $user->get('user_registered'),
                ];
            } catch (\Exception $error) {

                $users['error'][] = [
                    'error' => $error->getMessage()
                ];
            }
        }

        return $users;
    }
}
