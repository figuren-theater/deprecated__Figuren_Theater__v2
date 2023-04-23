<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Users;


/**
 * 
 */
class User
{

	/**
	 * [$instance description]
	 * @var WP_User
	 */
	protected $instance;

	public function get( $id_or_username )
	{
		if (is_int( $id_or_username )) {
			// no need for additional type or error checking,
			// this is done nicely inside of \WP_User::get_data_by, 
			// which is called by \get_user_by()
			$this->instance = \get_user_by( 'id', $id_or_username );
		} elseif (is_string( $id_or_username )) {
			$this->instance = \get_user_by( 'login', $id_or_username );
		}
#\do_action( 'qm/debug', $this->instance );   // https://querymonitor.com/docs/logging-variables/

		return $this->instance;
	}



	public static function id()
	{
		$static = new static;
		$instance = $static->get();
		return ( $instance ) ? $instance->ID : 0;
	}



	/**
	 * The register_new_user() function inserts a new user into the WordPress database. This function is used when a new user registers through WordPress’ Login Page. It differs from wp_create_user() in that it requires a valid username and email address but doesn’t allow to choose a password, generating a random one using wp_generate_password(). If you want to create a new user with a specific password or with additional parameters, use wp_create_user() or wp_insert_user() instead.

register_new_user() doesn’t handle the user creation itself, it simply checks the submitted username and email validity and generates a random password, relying on wp_create_user() to create the new User. If registration worked it sends a notification email to the user with his password using wp_new_user_notification(). In case of registration failure it returns a WP_Error().

register_new_user() also provides two useful hooks to customize validation rules or user registration process, register_post and registration_errors.
	 * @see https://developer.wordpress.org/reference/functions/register_new_user/
	 * @param  $user_login    (string) (Required) User's username for logging in
	 * @param  $user_email    (string) (Required) User's email address to send password and add
	 * 
	 * @return (int|WP_Error) Either user's ID or error on failure.
	 */
	public function create( string $user_login, string $user_email )
	{
		try {
			return register_new_user( $user_login, $user_email );

		} catch ( Exception $WP_Error  ) {
		    do_action( 'qm/error', $WP_Error  );   // https://querymonitor.com/docs/logging-variables/
#		    return 0;
		}
	}

	public function add_to_site( int $user_id, string $user_role = '', int $site_id = 0)
	{
		$site_id   = ( !empty( $site_id )) ? $site_id : \get_current_blog_id();
		$user_role = ( !empty( $user_role )) ? $user_role : $this->get_default_role();

		try {
			\add_user_to_blog( 
				$site_id,
				$user_id,
				$user_role
			);
		} catch ( Exception $WP_Error  ) {
		    do_action( 'qm/error', $WP_Error  );   // https://querymonitor.com/docs/logging-variables/
		}
	}


	protected function get_default_role()
	{
		return \get_option('default_role');
	}
}

#do_action( 'qm/error', $WP_Error  );   // https://querymonitor.com/docs/logging-variables/


