<?php
declare(strict_types=1);

namespace Figuren_Theater\inc;

/**
 * Helper
 */
class helper
{

	/**
	 * Determines if a post, identified by the specified ID, exist
	 * within the WordPress database.
	 * 
	 * @see https://tommcfarlin.com/wordpress-post-exists-by-id/
	 *
	 * @param    int    $id    The ID of the post to check
	 * @return   bool          True if the post exists; otherwise, false.
	 * @since    1.0.0
	 */
	public static function post_id_exists( Int $id ) : bool
	{
		return is_string( \get_post_status( $id ) );
	}

}