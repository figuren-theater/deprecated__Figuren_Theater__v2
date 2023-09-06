<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Users;

class User_List implements EventManager\SubscriberInterface
{
	public function hide_ft_bot_user( \WP_User_Query $user_search ) {
		global $current_user;
		$current_user_login = $current_user->user_login;

		$hidden_user_login = Users\ft_bot::NAME;

		if (
			// dont hide anything if this user is loged in
			$current_user_login != $hidden_user_login
			&&
			// or if its a network_admin
			!current_user_can( 'manage_sites' )
		) {
			\add_filter("views_users", [$this,"recalc__views_users"]);

			global $wpdb;
			$user_search->query_where = str_replace(
				"WHERE 1=1",
				"WHERE 1=1 AND {$wpdb->users}.user_login != '{$hidden_user_login}'",
				$user_search->query_where
			);
		}
	}

	public function recalc__views_users( array $views ) : array
	{
		$_hiddenusers_role = Users\ft_bot::ROLE;

		$users = \count_users();
		$admins_num = $users['avail_roles'][ $_hiddenusers_role ] - 1;
		$all_num = $users['total_users'] - 1;
		$class_adm = ( strpos($views[ $_hiddenusers_role ], 'current') === false ) ? "" : "current";
		$class_all = ( strpos($views['all'], 'current') === false ) ? "" : "current";
		$views[ $_hiddenusers_role ] = '<a href="users.php?role='.$_hiddenusers_role.'" class="' . $class_adm . '">' . \translate_user_role( ucwords($_hiddenusers_role) ) . ' <span class="count">(' . $admins_num . ')</span></a>';
		$views['all'] = '<a href="users.php" class="' . $class_all . '">' . \__('All') . ' <span class="count">(' . $all_num . ')</span></a>';

		return $views;
	}



	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			'pre_user_query' => 'hide_ft_bot_user',
		);
	}

}

