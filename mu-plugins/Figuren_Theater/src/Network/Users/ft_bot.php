<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Users;


/**
 * our machine-user
 * called 'ft_bot'
 *
 * maybe chance for enhancements
 * 'Creates and maintains the SysBot User'
 * @see https://gist.github.com/franz-josef-kaiser/4636113 
 */
class ft_bot extends User
{
	
	const NAME = 'ft_bot';
	const ROLE  = 'editor';		
	const EMAIL = 'info+bot@figuren.theater';		

	public function get( $username = self::NAME )
	{

		parent::get( $username );

		// make sure we have a valid WP_User
		if ( ! $this->instance instanceof \WP_User )
		{
			// user doesn't exist yet, so create one
			$this->instance = new \WP_User( $this->create( $username, self::EMAIL ) );

			// a new created user will ever be part of the current site
			// so we can leave early
			return $this->instance;
		}
		

		/**
		 * Check if the existing user is member of current blog
		 * 
		 * @see https://developer.wordpress.org/reference/functions/is_user_member_of_blog/
		 * @return bool
		 */
		if ( ! \is_user_member_of_blog( $this->instance->ID ) )
			$this->add_to_site( $this->instance->ID, self::ROLE );

		// everything fine, return the WP_User-obj
		return $this->instance;
	
	}


}