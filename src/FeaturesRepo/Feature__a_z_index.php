<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Taxonomies;


class Feature__a_z_index extends Features\Feature__Abstract implements EventManager\SubscriberInterface
{

	const SLUG = 'a-z-index';

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
		);
	}


	public function enable() : void 
	{
		// 
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_az_index'], 5); // must be between 0 and 10

		// load related blocks
		// ...TODO
	}

	public function enable__on_admin() : void {}

	public function disable() : void
	{
#		\add_action( 'init', [$this, 'disable__ft_az_index__term_auto_creation'], 9 ); // 9!!! // must be between 0 and 10
	}
/*
	public function disable__ft_az_index__term_auto_creation()
	{
		$_tax = \Figuren_Theater\API::get('TAX')->get( Taxonomies\Taxonomy__ft_az_index::NAME );
		array_map(
			function( $post_type ) use ( $_tax )
			{
				\remove_action( "save_post_{$post_type}", [$_tax, "set_default_taxonomy_terms"], 10 );
			},
			$_tax->post_types
		);
	}*/


	/**
	 * This taxonomy is loaded ....
	 */
	public function modify_Taxonomy__ft_az_index()
	{
#		\add_action('admin_head-index.php', [ \Figuren_Theater\API::get('TAX')->get( Taxonomies\Taxonomy__ft_az_index::NAME ), 'show_icon_at_a_glance' ] );

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_az_index::NAME, 'args', [
#Later			'public' => true,
#Maybe			'publicly_queryable' => true,
			'query_var' => true,
#Later			'rewrite' => [
#Later				'slug' => Taxonomies\Taxonomy__ft_az_index::SLUG,
#Later				'with_front' => false
#Later			],
		] );
	}


}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
