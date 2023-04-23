<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Users;
use Figuren_Theater\SiteParts;



/**
 * 
 */
class Taxonomy__ft_site_shadow extends Taxonomy__Abstract implements EventManager\SubscriberInterface, SiteParts\Data__CanAddYoastTitles__Interface
{


	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_site_shadow';

	/**
	 * The Rewrite Slug
	 */
	const SLUG = 'von';

	/**
	 * Terms of our TAX, that the current post has
	 * 
	 * @var WP_Term[]|false|WP_Error    Array of WP_Term objects on success, false if there are no terms
	 *                                  or the post does not exist, WP_Error on failure.
	 */
	protected $current_ft_site_shadows = [];


	protected $menu_icon_charcode = 'f124';

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
#DisabledToTestFormality2Approve2Site__1			
'shadow_tax_before_create' => [ 'allow_automatic_term_creation_from_shadowed_post_type' ],

			// prevent this tax from being overwritten by distribution
			// because it is not available on every (synced) post
			'dt_syncable_taxonomies' => ['dt_syncable_taxonomies', 10, 2 ],

			// Replace typical 'Author' stuff of distributed posts,
			// instead use the terms of this taxonomy

			// this is used by twentytwenty
			'get_the_author_display_name' => 'filter__get_the_author_display_name',
			'author_link' => 'filter__author_link',

			// Add our menu-Icon to the 'At a Glance' Dashboard Widget
			'admin_head-index.php' => 'show_icon_at_a_glance',

		);
	}



	/**
	 * Defines the desired use of Yoast SEO Variables.
	 * 
	 * Returns 'wpseo_titles' sub-options
	 * for this particular data-type.
	 *
	 * This sets the defaults used in meta-tags 
	 * like <title> and <meta type="description" ...>
	 * or in opengraph related ones.
	 *
	 * @see       plugins\wordpress-seo\inc\options\class-wpseo-option-titles.php
	 *
	 * @see       https://trello.com/c/D7lFumgs/137-yoast-seo 
	 * @see       https://yoast.com/help/list-available-snippet-variables-yoast-seo/
	 *
	 * @package FT_PROTOTYPE_TITLE_TAG_MANAGER
	 * @version 2022.04.14
	 * @author  Carsten Bach
	 *
	 * @example for taxonomies   
		return [
			'title'                        => '%%title%% %%page%% %%sep%% %%sitename%%',
			'metadesc'                     => '%%excerpt%%',
			'display-metabox'              => true,  // show some metabox for this data
			'noindex'                      => false, // prevent robots indexing
			'ptparent'                     => 0, // 
		];
	 *
	 * @return  Array       list of 'wpseo_titles' definitions 
	 *                      for this posttype or taxonomy
	 */
	public static function get_wpseo_titles() : array
	{
		return [
			'title'                        => __('%%pt_plural%% from %%term_title%% %%sep%% %%page%% %%sep%% %%sitename%%','figurentheater'),
			'metadesc'                     => __('%%sitename%% in %%ft_geolocation_last%% %%sep%% %%term_description%%','figurentheater'),
			// 'display-metabox'              => true,  // show some metabox for this data
			'noindex'                      => true, // prevent robots indexing
			// 'ptparent'                     => 'post', // 
		];
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
		$ft_site__TAX_shadow = new TAX_Shadow( $this::NAME, Post_Types\Post_Type__ft_site::NAME);
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $ft_site__TAX_shadow ); 
	}



	/**
	 * Only allow the creation of shadow terms,
	 * if we are on the root site.
	 *
	 * Four thousands of reasons, this tax must be network available.
	 * But only visible to and usable from the root-site. 
	 * Sure this is a root-only function, but by the design of the called 
	 * filter, we have to send our 99%-abort for all the network
	 * and only call re-activationb for the 1% - our root portal 
	 * at https://figuren.theater
	 *
	 * @see // https://github.com/carstingaxion/shadow-taxonomy/commit/0c3f0545f4a8efebe5c960875c8bd069d68e64a8 
	 * 
	 * @param  WP_Post $post [description]
	 * 
	 * @return bool          [description]
	 */
	public function allow_automatic_term_creation_from_shadowed_post_type( \WP_Post $post ) : bool
	{
		if ( Post_Types\Post_Type__ft_site::NAME === $post->post_type )
			return \Figuren_Theater\is_ft_core_site('root');

		// all others
		return true;
	}


	public function prepare_post_types() : array
	{
		return $this->post_types = [
			'post',
#			'event',
#			'ft_job',
			Post_Types\Post_Type__ft_production::NAME,
		];
	}


	protected function prepare_labels() : array
	{
		return $this->labels = [
			# Override the base names used for labels:
			'singular' => __('Website','figurentheater'),
			'plural'   => __('Websites','figurentheater'),
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
#			'show_tagcloud' => false,
#			'show_in_nav_menus' => false,
			'show_in_quick_edit' => false,
			'show_in_rest'  => true,
#			'show_admin_column'  => false,
			'rewrite'       => false,
			'capabilities' => array(
#					'manage_terms'  =>   'manage_'.$this->tax,
#					'manage_terms'  =>   'manage_categories',
				'manage_terms'  =>   'manage_sites',
				'edit_terms'    =>   'edit_' . $this::NAME, // this should only be done by the CRON
				'delete_terms'  =>   'delete_' . $this::NAME, // this should only be done by the CRON
#					'assign_terms'  =>   'assign_'.$this->tax,
				'assign_terms'  =>   'edit_posts',
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
#			'meta_box' => 'simple', //KEEP DISABLED // triggers JS problems in Gutenberg when editing 'ft_site'

			# Show this taxonomy in the 'At a Glance' dashboard widget:
#			'dashboard_glance' => false,

			# Add a custom column to the admin screen:
			'admin_cols' => [
				'updated' => [
					'title'       => __('Updated','figurentheater'),
					'meta_key'    => 'updated_date',
					'date_format' => 'd/m/Y'
				],
			],
		];
	}


	protected function _get_current_ft_site_shadows() : array
	{
		global $post;

		if ( isset( $this->current_ft_site_shadows[ $post->ID ] ) )
			return $this->current_ft_site_shadows[ $post->ID ];

		$_shadows = get_the_terms( $post, $this::NAME );
		if ($_shadows instanceof WP_Error || false === $_shadows )
			return $this->current_ft_site_shadows;

		return $this->current_ft_site_shadows[ $post->ID ] = $_shadows;
	}


	public function filter__author_link( $author_link ) : string
	{
		global $post;

		if ( is_admin() )
			return $author_link;

		// ugly guard clausing
		// because this gets loaded and loaded and loaded
		// totally independent from its is_admin() condition
		if ( ! $post instanceof \WP_Post )
			return $author_link;

		// cache me
		$this->_get_current_ft_site_shadows();

		// guard clausing
		if ( ! isset( $this->current_ft_site_shadows[ $post->ID ] ) )
			return $author_link;

		// TODO maybe remove
#		$_tax_obj = get_taxonomy( $this::NAME );
		$_tax_obj = \Figuren_Theater\API::get('TAX')->get( $this::NAME );

		// because the slug of the shadow tax is 
		// the same like the post slug, we just use it
		return site_url( $_tax_obj->rewrite['slug'] . '/' . $this->current_ft_site_shadows[ $post->ID ][0]->slug );
	}



	/**
	 * [filter__get_the_author_display_name description]
	 *
	 * @todo Have a look inti this loading issue, described down below.
	 * 
	 * @param  [type] $author_display_name [description]
	 * @return [type]                      [description]
	 */
	public function filter__get_the_author_display_name( $author_display_name ) : string
	{

		if ( is_admin() )
			return $author_display_name;

		global $post;

		// ugly guard clausing
		// because this gets loaded and loaded and loaded
		// totally independent from its is_admin() condition
		if ( ! $post instanceof \WP_Post )
			return $author_display_name;

		// cache me
		$this->_get_current_ft_site_shadows();

		// guard clausing
		if ( ! isset( $this->current_ft_site_shadows[ $post->ID ] ) )
			return $author_display_name;

		return $this->current_ft_site_shadows[ $post->ID ][0]->name;
	}

} // END Class Taxonomy__ft_site_shadow
#} // endif class_exists
