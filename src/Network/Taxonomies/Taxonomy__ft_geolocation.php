<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\SiteParts;



/**
 * 
 */
class Taxonomy__ft_geolocation extends Taxonomy__Abstract implements EventManager\SubscriberInterface, SiteParts\Data__CanAddYoastTitles__Interface, SiteParts\Data__CanAddYoastVariables__Interface
{


	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_geolocation';

	/**
	 * The Rewrite Slug
	 */
	const SLUG = 'in';


	protected $menu_icon_charcode = 'f11d'; # https://developer.wordpress.org/resource/dashicons/#admin-site-alt


	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		$_ft_site = Post_Types\Post_Type__ft_site::NAME;
		$_ft_production = Post_Types\Post_Type__ft_production::NAME;
		return array(
			'save_post_post' => ['set_default_taxonomy_terms', 10, 2],
#			'save_post_EVENT' => ['set_default_taxonomy_terms', 10, 2]
#			'save_post_ft_job' => ['set_default_taxonomy_terms', 10, 2]
			"save_post_{$_ft_site}" => ['set_default_taxonomy_terms', 10, 2],
			"save_post_{$_ft_production}" => ['set_default_taxonomy_terms', 10, 2],

			// make sure this gets synced,
			// even when this tax is not registered as 'public'
			'dt_syncable_taxonomies' => ['dt_syncable_taxonomies', 10, 2 ],

			// Add our menu-Icon to the 'At a Glance' Dashboard Widget
			// 'admin_head-index.php' => 'show_icon_at_a_glance', # done via feature-plugin 'Feature__ueberregionale-inhalte'

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
			'title'               => __('%%pt_plural%% from %%term_title%% %%sep%% %%page%% %%sep%% %%sitename%%','figurentheater'),
			'metadesc'            => __('%%sitename%% in %%term_hierarchy%%','figurentheater'),
			// 'display-metabox'     => true,  // show some metabox for this data
			// 'noindex'             => false, // prevent robots indexing
			// 'ptparent'            => 'post', // 
		];
	}


	public static function get_wpseo_variables() : array
	{
		// as alternative to:
		// %%ct_ft_geolocation%%, 
		// which is similar to
		// %%term_hierarchy%%
		return [
			[
				'%%ft_geolocation_last%%',
				function()
				{
					$_geo_terms = \get_the_terms( \get_the_ID(), self::NAME );
					if (!is_array( $_geo_terms ) || empty( $_geo_terms ) )
						return '';

					$_last = array_pop( $_geo_terms );
					if ( ! $_last instanceof \WP_Term )
						return '';
					
					return $_last->name;
				},
				'advanced',
				__('Shows City, State or Country','figurentheater')
			],
		];
	}


	public function set_default_taxonomy_terms( Int $post_id, \WP_Post $post )
	{
		// run only at home
		// WHY??
		// if ( \ms_is_switched() )
			// return;

		$this->TaxonomiesAutoTerms->set_default_taxonomy_terms( $post_id, $post );
	}



	protected function prepare_tax() : void {
		$this->TaxonomiesAutoTerms = new TaxonomiesAutoTerms( $this::NAME );
	}



	public function prepare_post_types() : array
	{
		return $this->post_types = [
			'post',
#			'EVENT',
#			'ft_job',
			Post_Types\Post_Type__ft_site::NAME,
			Post_Types\Post_Type__ft_production::NAME,
		];
	}


	protected function prepare_labels() : array
	{
		return $this->labels = [
			# Override the base names used for labels:
			'singular' => __('Location','figurentheater'),
			'plural'   => __('Locations','figurentheater'),
			'slug'     => $this::SLUG
		];
	}

	public function register_taxonomy__default_args() : array
	{
		// TODO move this into sep. dev-plugin or sommething similiar ...
		if (
			\is_super_admin()
			&&
			defined('WP_ENVIRONMENT_TYPE')
			&&
			(
				'production' !== \WP_ENVIRONMENT_TYPE
				||
				(
					'production' === \WP_ENVIRONMENT_TYPE
					&&
					defined('WP_DEBUG')
					&&
					constant('WP_DEBUG')
				)
			)
		) {
			$_show_ui = true;
		} else {
			$_show_ui = false;
		}
		return [
			'label'         => $this->labels['plural'], // fallback
			'public' => false,
#			'show_ui' => (defined('WP_DEBUG') && constant('WP_DEBUG')) ? WP_DEBUG : false,
#			'show_ui' => false,
			'show_ui' => $_show_ui,
			'hierarchical'  => true,
#			'show_tagcloud' => false,
#			'show_in_nav_menus' => false,
			'show_in_rest'  => true,
#			'show_admin_column'  => false,
			'show_admin_column'  => $_show_ui,
			'show_in_quick_edit' => false, // as for now, this taxonomy is only set programmatically
			// 'show_admin_column'  => true,
			'rewrite'       => false,
			'capabilities'               => array(
				'manage_terms'               => 'manage_categories',
				'edit_terms'                 => 'manage_categories',
				'delete_terms'               => 'manage_categories',
				'assign_terms'               => 'edit_posts',
			),
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
#			'meta_box' => 'simple', //KEEP DISABLED // triggers JS problems in Gutenberg when editing 'ft_site'

			# Show this taxonomy in the 'At a Glance' dashboard widget:
#			'dashboard_glance' => false,

		];
	}


	/**
	 * Filters the taxonomies that should be synced.
	 *
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
		return array_merge( $allowed_taxonomies, [ self::NAME ] );
	}

} // END Class Taxonomy__ft_geolocation
#} // endif class_exists
