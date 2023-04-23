<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Sync;
use Figuren_Theater\Network\Taxonomies;


/**
 * 
 */
class Feature__core__contenthub extends Features\Feature__Abstract
{
	const SLUG = 'core-contenthub';

	public function enable() : void 
	{

		\add_action( 'init', [$this, 'modify_Post_Type__ft_production'], 5); // must be between 0 and 10
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_production_shadow'], 5); // must be between 0 and 10
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_site_shadow'], 5); // must be between 0 and 10

		// \add_action( 'init', [$this, 'debug'], 42 );
	}


	public function modify_Post_Type__ft_production()
	{
	
		$PT_ft_production = \Figuren_Theater\API::get('PT')->get( Post_Types\Post_Type__ft_production::NAME );

		// $_taxonomies    = array_merge( $PT_ft_production->args['taxonomies'], [ 
		// 		Taxonomies\Taxonomy__ft_site_shadow::NAME
		// 	] );
		
		$_admin_cols    = array_merge( $PT_ft_production->args['admin_cols'], [
				Taxonomies\Taxonomy__ft_site_shadow::NAME => [
					'taxonomy' => Taxonomies\Taxonomy__ft_site_shadow::NAME
				],
			] );
		$_admin_filters = array_merge( $PT_ft_production->args['admin_filters'], [
				Taxonomies\Taxonomy__ft_site_shadow::NAME => [
					'title'    => 'All Websites',
					'taxonomy' => Taxonomies\Taxonomy__ft_site_shadow::NAME
				],
			] );

		\Figuren_Theater\API::get('PT')->update( Post_Types\Post_Type__ft_production::NAME, 'args', [

			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,

			// 'taxonomies'          => $_taxonomies,

			# Add the post type to the site's main RSS feed:
			'show_in_feed' => false,
			#
			'quick_edit' => false,

			'admin_cols' => $_admin_cols,

			# Add some dropdown filters to the admin screen:
			'admin_filters' => $_admin_filters,
		] );
	}



	public function modify_Taxonomy__ft_production_shadow()
	{

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_production_shadow::NAME, 'args', [
			// 'public' => true,
			// 'show_tagcloud' => true,
			// 'show_ui' => false,
			'show_admin_column' => false,
			'show_in_quick_edit' => false,
			// 'dashboard_glance' => true,
			// 'rewrite'       => array(
			// 	'slug' => Taxonomies\Taxonomy__ft_production_shadow::SLUG,
			// 	'with_front' => false
			// ),
		] );
	}


	/**
	 * This taxonomy is loaded all over the network,
	 * the UI and also its automatic shadowing
	 * is only load on https://figuren.theater 
	 */
	public function modify_Taxonomy__ft_site_shadow()
	{

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_site_shadow::NAME, 'args', [
			'public' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'show_admin_column' => true,
			'dashboard_glance' => true,
			'rewrite'       => array(
				'slug' => Taxonomies\Taxonomy__ft_site_shadow::SLUG,
				'with_front' => false
			),
		] );
	}


	public function enable__on_admin() : void 
	{

		// register as 'distributable_post_types'
		// 
		// Yes, this is called from within 
		// Sync\AutoDistribute, so it should be there,
		// but for the very only one case, where
		// this is the site, which is distributed to,
		// and which uses a non-public post_type,
		// the normal func. won't reach the point to add this to the 
		// 'distributable_post_types' filter
		// 
		// so we have to ;)
		// 
		// this makes the UI on the receiving site work
		new Sync\AllowDistribution( Post_Types\Post_Type__ft_site::NAME );
		// 
		// this makes the UI on the receiving site work
		new Sync\AllowDistribution( Post_Types\Post_Type__ft_production::NAME );
	}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
