<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Sync;

use Figuren_Theater\inc\EventManager;

/**
 * Allow sudden post_type to be distributed using the glorious
 * 'Distributor' Plugin by 10up
 * 
 * Add the given post_type to the array of allowed 
 * post_type slugs used by the Distributor-Plugin.
 *
 * By making this a class, we can call it directly,
 * if we want to just register a post_type for manual pushing and pulling, 
 * eg. 'wp_block' to make use of distributed re-usable blocks.
 *
 * @uses 'distributable_post_types' filter
 * 
 * @param  String $post_type [description]
 */
class AllowDistribution implements EventManager\SubscriberInterface {
	
	protected $post_type;

	function __construct( string $post_type ) {
		$this->post_type = $post_type;

		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $this );
	}

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array {
		return array(
			'distributable_post_types' => 'filter', 
		);
	}

	/**
	 * Filter post types that are distributable.
	 *
	 * @since 1.0.0
	 * @hook distributable_post_types
	 * @see https://10up.github.io/distributor/distributable_post_types.html 
	 *
	 * @param $distributable_post_types array Post types that are distributable. Default all post types except `dt_ext_connection` and `dt_subscription`.
	 *
	 * @return {array} Post types that are distributable.
	 */
	public function filter( $distributable_post_types ) {
		$distributable_post_types[ $this->post_type ] = $this->post_type;
		return $distributable_post_types;
	}
}
