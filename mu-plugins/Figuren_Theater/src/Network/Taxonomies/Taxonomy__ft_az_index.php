<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Coresites\Post_Types as Core_Post_Types;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Users;



/**
 * 
 */
class Taxonomy__ft_az_index extends Taxonomy__Abstract implements EventManager\SubscriberInterface
{
	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_az_index';

	/**
	 * The Rewrite Slug
	 */
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
			// prevent this tax from being overwritten by distribution
			// because it is not available on every (synced) post
			'dt_syncable_taxonomies' => ['dt_syncable_taxonomies', 10, 2 ],
		);
	}



	/**
	 * Filters the taxonomies that should be synced.
	 *
	 * Remove current taxonomy,
	 * because this will not be synced but set automatically on some blogs.
	 * To make this work and happen, we have to remove this here,
	 * otherwise the tax terms will be reset to [] (an empty list) on every syndication.
	 *
	 * @since 1.0
	 * @hook dt_syncable_taxonomies
	 * @see https://10up.github.io/distributor/dt_syncable_taxonomies.html 
	 * @see https://10up.github.io/distributor/includes_utils.php.html#line462 
	 *
	 * @param {array}  $taxonomies  Associative array list of taxonomies supported by current post in the format of `$taxonomy => $terms`.
	 * @param {WP_Post} $post       The post object.
	 *
	 * @return {array} Associative array list of taxonomies supported by current post in the format of `$taxonomy => $terms`.
	 */
	public function dt_syncable_taxonomies( $allowed_taxonomies, $post )
	{
		return array_diff( $allowed_taxonomies, [ self::NAME ] );
	}


	protected function prepare_tax() : void
	{
		// Call this directly and THAT way
		// DONT use the EventManager for this
		\add_action( 'init', [$this, 'enable__ft_az_index__term_auto_creation'], 5); // 5 !!!
	}


	public function enable__ft_az_index__term_auto_creation()
	{
		$thisclass = $this;
		array_map(
			function( $post_type ) use ( $thisclass )
			{
				\add_action( "save_post_{$post_type}", [$thisclass, "set_default_taxonomy_terms"], 10, 2 );
			},
			$this->post_types
		);
	}


	public function prepare_post_types() : array
	{
		return $this->post_types = [
			'post',
			'page',
#			'event',
#			'ft_job',
			Core_Post_Types\Post_Type__ft_feature::NAME,
			Post_Types\Post_Type__ft_site::NAME,
			Post_Types\Post_Type__ft_production::NAME,
		];
	}


	protected function prepare_labels() : array
	{
		return $this->labels = [
			# Override the base names used for labels:
			'singular' => __('AZ-Index','figurentheater'),
			'plural'   => __('AZ-Indexes','figurentheater'),
			'slug'     => $this::SLUG
		];
	}


	public function register_taxonomy__default_args() : array
	{
		return [
			'label'         => $this->labels['plural'], // fallback
			'public' => false,
			'show_ui' => false,
			'hierarchical'  => false,
			'show_tagcloud' => false,
			'show_in_nav_menus' => false,
			'show_in_quick_edit' => false,
			'show_in_rest'  => true,
			'show_admin_column'  => false,
			'rewrite'       => false,
#			'capabilities' => array(
##					'manage_terms'  =>   'manage_'.$this->tax,
##					'manage_terms'  =>   'manage_categories',
#				'manage_terms'  =>   'manage_sites',
#				'edit_terms'    =>   'edit_' . $this::NAME, // this should only be done by the CRON
#				'delete_terms'  =>   'delete_' . $this::NAME, // this should only be done by the CRON
##					'assign_terms'  =>   'assign_'.$this->tax,
#				'assign_terms'  =>   'edit_posts',
#			),
		];

	}


	/**
	 * Default arguments for custom taxonomies
	 * Several of these differ from the defaults in WordPress' register_taxonomy() function.
	 * 
	 * https://github.com/johnbillion/extended-cpts/wiki/Registering-taxonomies#default-arguments-for-custom-taxonomies
	 */
	protected function register_extended_taxonomy__args() : array
	{
		return [
			# Use radio buttons in the meta box for this taxonomy on the post editing screen:
#			'meta_box' => 'simple', //KEEP DISABLED // triggers JS problems in Gutenberg when editing 'ft_site'

			# Show this taxonomy in the 'At a Glance' dashboard widget:
#			'dashboard_glance' => false,

			# Add a custom column to the admin screen:
#			'admin_cols' => [
#				'updated' => [
#					'title'       => 'Updated',
#					'meta_key'    => 'updated_date',
#					'date_format' => 'd/m/Y'
#				],
#			],
		];
	}




	// similar save function to store our tax data when a post is saved
	
	// http://wordpress.stackexchange.com/questions/41660/how-to-build-a-directory-with-indexes-in-wordpress/
	//http://www.kathyisawesome.com/424/alphabetical-posts-glossary/
	
	/* When the post is saved, saves our custom data */
	public function set_default_taxonomy_terms( Int $post_id, \WP_Post $post )
	{
		// // run only at home
		// if ( \ms_is_switched() )
		// 	return;

		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && \DOING_AUTOSAVE )
			return;

		// run only on publish
		if ( 'publish' !== $post->post_status )
			return;

		// 
		$has_feature__az_index = \is_object_in_term( 
			\Figuren_Theater\FT_wpdb::init()->get_ft_site_post_id(),
			Taxonomy__ft_feature_shadow::NAME,
			'a-z-index'
		);

		if ( !$has_feature__az_index )
			return;

		// OK, we're authenticated: we need to find and save the data
		$taxonomy = $this::NAME;

		// Clean from non-alphanumeric chars
		$clean_title = preg_replace("/[^a-zA-Z0-9\s]/", "", \sanitize_title($post->post_title));
		// get first relevant Character
		$first_char = strtolower( substr( $clean_title, 0, 1 ) );
#error_log($taxonomy);
#error_log($clean_title);
#error_log($first_char);

		if ( is_numeric( $first_char ) ) {
			\wp_set_post_terms( $post_id, '0-9', $taxonomy );
		} else {
			\wp_set_post_terms( $post_id, strtoupper($first_char), $taxonomy );
		}
	}

} // END Class Taxonomy__ft_az_index
#} // endif class_exists
