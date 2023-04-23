<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Post_Types;



/**
 * 
 */
class Taxonomy__ft_production_shadow extends Taxonomy__Abstract implements Taxonomy__LinksShadowedPost__Interface, EventManager\SubscriberInterface
{

	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_production_shadow';

	/**
	 * The Rewrite Slug
	 */
	// const SLUG = 'von';

	/**
	 * Terms of our TAX, that the current post has
	 * 
	 * @var WP_Term[]|false|WP_Error    Array of WP_Term objects on success, false if there are no terms
	 *                                  or the post does not exist, WP_Error on failure.
	 */
	protected $current_ft_production_shadows = [];



	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			
			// filter of \ShadowTaxonomy\Core which toggles the automatic term creation
			// 'shadow_tax_before_create' => [ 'allow_automatic_term_creation_from_shadowed_post_type', 10, 2 ],

			// prevent this tax from being overwritten by distribution
			// because this is THE LONG WAY problem
			'dt_syncable_taxonomies' => ['dt_syncable_taxonomies', 10, 2 ],

			// Add our menu-Icon to the 'At a Glance' Dashboard Widget
			// 'admin_head-index.php' => 'show_icon_at_a_glance',

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
		// Register shadow connection between this taxonomy and post_type
		$ft_production__TAX_shadow = new TAX_Shadow( $this::NAME, Post_Types\Post_Type__ft_production::NAME);
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $ft_production__TAX_shadow ); 
	}



	/**
	 * PREVENT the creation of shadow terms,
	 * if we are on the root site.
	 *
	 * Four thousands of reasons, this tax must be network available.
	 * But only visible to and usable from the user-sites. 
	 *
	 * @see // https://github.com/carstingaxion/shadow-taxonomy/commit/0c3f0545f4a8efebe5c960875c8bd069d68e64a8 
	 * 
	 * @param  WP_Post $post [description]
	 * 
	 * @return bool          [description]
	public function allow_automatic_term_creation_from_shadowed_post_type( bool $allow, \WP_Post $post ) : bool
	{
		if ( Post_Types\Post_Type__ft_production::NAME === $post->post_type )
			return ! \Figuren_Theater\is_ft_core_site('root');

		// all others
		return $allow;
	}
	 */


	public function prepare_post_types() : array
	{
		return $this->post_types = [
			'post',
			// 'event',
			// 'ft_job',
		];
	}


	protected function prepare_labels() : array
	{
		return $this->labels = [
			# Override the base names used for labels:
			'singular' => __('Production','figurentheater'),
			'plural'   => __('Productions','figurentheater'),
			'slug'     => '' #TODO
		];
	}

	public function register_taxonomy__default_args() : array
	{
		return [
			'label'         => $this->labels['plural'], // fallback
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'hierarchical'  => false,
			'show_tagcloud' => false,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_in_quick_edit' => true,
			'show_in_rest'  => true,
			'show_admin_column'  => true,
			'rewrite'       => false,
			#'capabilities' => array(
			#		'manage_terms'  =>   'manage_'.$this->tax,
			#		'manage_terms'  =>   'manage_categories',
			#	'manage_terms'  =>   'manage_sites',
			#	'edit_terms'    =>   'edit_' . $this::NAME, // this should only be done by the CRON
			#	'delete_terms'  =>   'delete_' . $this::NAME, // this should only be done by the CRON
			#		'assign_terms'  =>   'assign_'.$this->tax,
			#	'assign_terms'  =>   'edit_posts',
			#),
			// ! YEAHHH!!!!!!!
			// https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2687
			// 
			// [IN LONG]
			// Undocumented features are the best features.
			// https://core.trac.wordpress.org/ticket/40496#comment:4
			// 
			// [7520] introduced an undocumented feature whereby developers could
			// register a custom taxonomy with an 'args' parameter, consisting of
			// an array of config params that, when present, override corresponding
			// params in the $args array passed to wp_get_object_terms() when
			// using that function to query for terms in the specified taxonomy.
			// https://core.trac.wordpress.org/ticket/40496#comment:6
			// 
			// I had no idea that this weird feature existed - 
			// it was introduced in [7520], doesn't appear to be documented anywhere, 
			// and is a pattern I've never seen used elsewhere in WordPress. 
			// It's not the worst idea to have taxonomy-specific 
			// query arguments (or post types, for that matter!), 
			// but perhaps we should advertise it better.
			// https://core.trac.wordpress.org/ticket/40496#comment:2
			// 
			// [IN SHORT]
			// when sort=true, all attached args 
			// will be added to every Term_Query of this TAX
			'sort' => true,
			'args' => array(
				'orderby' => 'term_order',
			),
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
			// 'meta_box' => 'simple', //KEEP DISABLED // triggers JS problems in Gutenberg when editing 'ft_production'

			# Show this taxonomy in the 'At a Glance' dashboard widget:
			// 'dashboard_glance' => false,

			# Add a custom column to the admin screen:
			#'admin_cols' => [
			#	'updated' => [
			#		'title'       => 'Updated',
			#		'meta_key'    => 'updated_date',
			#		'date_format' => 'd/m/Y'
			#	],
			#],
		];
	}


	protected function _get_current_ft_production_shadows() : array
	{
		global $post;

		if ( isset( $this->current_ft_production_shadows[ $post->ID ] ) )
			return $this->current_ft_production_shadows[ $post->ID ];

		$_shadows = get_the_terms( $post, $this::NAME );
		if ($_shadows instanceof WP_Error || false === $_shadows )
			return $this->current_ft_production_shadows;

		return $this->current_ft_production_shadows[ $post->ID ] = $_shadows;
	}


} // END Class Taxonomy__ft_production_shadow
