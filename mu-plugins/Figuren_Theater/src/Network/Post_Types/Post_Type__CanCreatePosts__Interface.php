<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;


interface Post_Type__CanCreatePosts__Interface
{

	/**
	 * Get the post data as a wp_insert_post compatible array.
	 *
	 * @return array
	 */
	public function get_post_data() : array;

	/**
	 * Get all the post meta as a key-value associative array.
	 *
	 * @return array
	 */
	public function get_post_meta() : array;
}
