<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;

use Figuren_Theater\Coresites\Post_Types as Core_Post_Types;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\Network\Users;

// use Shadow_Taxonomy\Core as Shadow_Taxonomy;
use Distributor\InternalConnections as Connections;


/**
 * Responsible for registering the 'ft_site' post_type 
 */

/**
 * We need this post_type on every site,
 * but we don't need its UI everywhere.
 * 
 * This post_type should be automatically 
 * created on blog_creation from within 
 * https://mein.figuren.theater and 
 * afterwards maintained from the same place.
 * Some data will be updated from updated options 
 * of the corresponding user-site e.g. https://abc.figuren.theater
 * and maybe some from the main portal https://figuren.theater.
 * 
 */
class Post_Type__ft_site extends Post_Type__Abstract implements Post_Type__CanCreatePosts__Interface, Post_Type__CanBeAutoDistributed__Interface, EventManager\SubscriberInterface
{

	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_site';

	/**
	 * The Class Object
	 */
	static private $instance = null;

	/**
	 * Core class used for interacting with a multisite site.
	 * 
	 * @var WP_SITE
	 */
	protected $blog = null;

	protected $arguments = [];


	function __construct( int $blog_id = 0, $WP_Site = null, Array $arguments = [] )
	{

		if (0 >= $blog_id)
			return;

		if (0 < $blog_id && $WP_Site instanceof \WP_Site ) {
			$this->blog = $WP_Site;
		}

		// get the instance from DB or cache
		$_blog = \WP_Site::get_instance(  $blog_id );
		// not a WP_Error obj
		if (!$_blog instanceof \WP_Error ) {
			$this->blog = $_blog;
		}

		if (!empty($arguments)) {
			$this->arguments = $arguments;
		}
	}

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(

			// do not trigger extra calls on wp_insert_post,
			// like when hooking on 'save_post_'.$this::NAME or 'wp_after_insert_post'
			// this is better & goes far earlier
			'wp_insert_post_data' => [ 'reset_permalink_to_post_title', 10, 3], // WORKING ...

			'bulk_actions-edit-' . self::NAME => 'disable_bulk_edits',


			// after distributer registers its 'update_syndicated()' --> WHY ??
	// TEST		
			'init' => [ 'on_init', 12 ], // works and is NEEDED !!!
			// 'init' => [ 'on_init', 0 ], // ?? NO ????? NO !!!
			// 'plugins_loaded' => [ 'on_init', 3 ],
			// 
			// make the filters available to the installation 
			// 
			// 'Figuren_Theater\Onboarding\Sites\Installation\insert_first_content' => [ 'on_init', -10 ],
			// 'wp_initialize_site' => [ 'on_init', 800 ],
	// TEST
			// 'Figuren_Theater\Onboarding\Sites\Installation\insert_first_content' => [ 'on_init', 1 ], // the 'ft_site'-post is created on '0'

			// 'plugins_loaded' => [ 'on_init', 10 ], #TEST
		);
	}

	protected function prepare_pt() : void {}
	// before distributor
	// protected function prepare_pt() : void {
	

	public function on_init() {
	// error_log('######################    sync_options_to_post ACTIONs IN PLACE');

		//
		$_ft_geo_tax_name = Taxonomies\Taxonomy__ft_geolocation::NAME;

		\add_action( 'update_option_blogname'                          , [ $this, 'sync_options_to_post'] , 10, 3 );
		\add_action( 'update_option_blogdescription'                   , [ $this, 'sync_options_to_post'] , 10, 3 );
		\add_action( 'update_option_site_icon'                         , [ $this, 'sync_options_to_post'] , 10, 3 );
		\add_action( "update_option_default_{$_ft_geo_tax_name}_terms" , [ $this, 'sync_options_to_post'] , 10, 3 );
		\add_action( 'update_option_siteurl'                           , [ $this, 'sync_options_to_post'] , 10, 3 );
		\add_action( 'update_option_home'                              , [ $this, 'sync_options_to_post'] , 10, 3 );
	}



	public function disable_bulk_edits( array $actions ) : array {
		unset( $actions[ 'edit' ] );
		return $actions;
	}

	/**
	 * Get the post data as a wp_insert_post compatible array.
	 *
	 * @return array
	 */
	public function get_post_data() : array
	{
		if (null === $this->blog)
			return [];

		return [
			'post_author'    => ( isset( $this->arguments['user_id'] ) ) ? $this->arguments['user_id'] : Users\ft_bot::id(), 
			'post_title'     => $this->blog->blogname,
			'post_name'      => $this->get_cleaned_site_url( \get_site_url( (int) $this->blog->blog_id ) ), // ID
			'post_status'    => 'publish', // start with private, switch to publish on later point
			'post_type'      => self::NAME,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'tax_input'      => $this->get_post_tax(),
			'meta_input'     => $this->get_post_meta(),
		];
	}

	/**
	 * Get all the post meta as a key-value associative array.
	 *
	 * @return array
	 */
	public function get_post_meta() : array
	{
		return [
			'_ft_blog_id' => $this->blog->blog_id,
			'_ft_siteurl' => $this->blog->siteurl,
		];
	}

	/**
	 * Get all taxonomies and its terms (IDs) 
	 * as multidimesnional array, 
	 * properly prepared to be used 
	 * as part of wp_insert_post.
	 *
	 * Structural Example:
	 *      'tax_input'    => array(
	 *         'hierarchical_tax'     => array( 13, 10 ),
	 *         'non_hierarchical_tax' => 'tax name 1, tax name 2',
	 *     ),
	 *
	 * @TODO include Taxonomies\Taxonomy__ft_geolocation::NAME in default
	 */
	public function get_post_tax() : array
	{
		// 0. prepare return
		$tax_input = [];

		// 1. get new level post ID (from $args)
		// if ( isset( $this->arguments[ 'ft_level' ] ) && is_numeric( $this->arguments[ 'ft_level' ] ) ) {
		if ( isset( $this->arguments[ 'ft_level' ] ) && intval( $this->arguments[ 'ft_level' ] ) ) {

			// 2. get level_shadow_term from ft_level-post and set on ft_site
			// +
			// 3. get feature_shadow_terms from ft_level-post and set on ft_site
			// together ...
			$tax_input = array_merge( 
				$tax_input,
				// $this->prepare_new_ft_level_relative_data( (int) $this->arguments[ 'ft_level' ] )
				Core_Post_Types\Post_Type__ft_level::prepare_new_ft_level_relative_data( (int) $this->arguments[ 'ft_level' ] )
			);
		}

		// 6. create new UtilityFeaturesManager to work with,
		// because our normal one is created later on 'admin_menu',
		// but is not available during signup.
		// 
		// $UtilityFeaturesManager = new Features\UtilityFeaturesManager; // generates FATAL error // https://trello.com/c/hm6v7rfx/301-vorbereitung-140
		
		# DISABLED during the refactoring uf UtilityFeatures
		# default features per site are handled by levels
		# default features per post_type will be handled by 
		# the normal behaviour of 'hm-utility' Plugin
		# 
		#$UtilityFeaturesManager = \Figuren_Theater\FT::site()->UtilityFeaturesManager;
		#$tax_input[ Features\UtilityFeaturesManager::TAX ] = 
		#	join(',', $UtilityFeaturesManager->get_UtilityFeatures_defaults( 
		#		$UtilityFeaturesManager->get_UtilityFeatures_for_post_type( self::NAME )
		#	) );

		return $tax_input;
	}


	public static function get_cleaned_site_url( string $url ) : string {
		

		// $url = \home_url( '/', 'https' );

		// remove leading 'https://' (8 char)
		$cleaned_url = substr( $url, 8 );

		// remove trailing slash
		$cleaned_url = \untrailingslashit( $cleaned_url );

		// mask slashes with dashes
		$cleaned_url = str_replace( '/', '-', $cleaned_url );
		$cleaned_url = sanitize_title_with_dashes( $cleaned_url );

		return $cleaned_url;
	}

	/**
	 * Switch the level on ft_site-post
	 *
	 * Used during initial ft_site setup,
	 * and on updating 'ft_level_shadow'-terms on 
	 * existing 'ft_site'-posts.
	 *
	 * If a 'ft_site' already has a level 
	 * (aka 'ft_level_shadow'-term) assigned to it,
	 * level and also related features will be set 
	 * completely new and overwrite existing terms.
	 *
	 * If there was no level till now, 
	 * existing Features will be kept, 
	 * when a level is assigned the first time
	 * 
	 * @return [type] [description]
	 *
	 * @TODO all....
	 */
	public function switch_level() {}


	/**
	 * MOVED INTO Coresites\Post_Types\Post_Type__ft_level.php
	 *
	 * 
	 * Prepares 'ft_feature_shadow'- and 'ft_level_shadow'-terms 
	 * in case our 'ft_site' receives a new "level".
	 *
	 * So we head over to https://websites.fuer.figuren.theater (TODO make more flexible)
	 * and get all 'ft_feature_shadow'-terms assigned to our requested new 'ft_level'.
	 * Also we get the asociated 'ft_level_shadow'-term from the sitemanager-website.
	 * Both is returned in an asociative array.
	 * 
	 * @param  int    $new_ft_level  ID of 'ft_level' Post on https://websites.fuer.figuren.theater 
	 * @return array  []            'ft_feature_shadow'- and 'ft_level_shadow'-term-slugs 
	 *                               as array with keys named by their taxonomies

	public function prepare_new_ft_level_relative_data( int $new_ft_level ) : array
	{
		// 1. switch to (a) sitemanagement-blog, which has the required 'ft_level'-data
		// TODO // find nice way to get (one of many) sitemanagement-blogs
		$sitemanagement_blog = array_flip( FT_CORESITES );
		$sitemanagement_blog = $sitemanagement_blog['webs'];
		\switch_to_blog( $sitemanagement_blog );

		// 2. convert given ID for the new level into Pseudo WP_Post,
		// because our post_type of 'ft_level' is not available during 
		// site-creation from https://mein.figuren.theater
		// or on updating an existing 'ft_site' from https://some.where.figuren.theater
		$new_ft_level_pseudo_post = (object) [
			'ID' => $new_ft_level,
		];

		// 3. get 'ft_level_shadow'-term from 'ft_level'-post 
		// and prepare for 'ft_site'
		$TAX_Shadow = Taxonomies\TAX_Shadow::init();
		$new_ft_level_shadow_term = $TAX_Shadow->get_associated_term( $new_ft_level_pseudo_post, Taxonomies\Taxonomy__ft_level_shadow::NAME );

		// 4. get 'ft_feature_shadow'-terms from 'ft_level'-post 
		// and prepare for 'ft_site'
		// 
		// 4.1 Init our WP_Query wrapper
		$ft_term_query = \Figuren_Theater\FT_Term_Query::init();
		//    Because 'ft_level' posts can only have 'ft_feature_shadow' terms, 
		//    as it is the only assigned taxonomy, 
		//    we can just get all terms from this post.
		$new_ft_feature_shadow_terms = $ft_term_query->find_by_post( $new_ft_level_pseudo_post->ID );
		#$new_ft_feature_shadow_term_ids = \wp_filter_object_list( $new_ft_feature_shadow_terms, [], 'and', 'term_id' );
		$new_ft_feature_shadow_term_slugs = join(',', \wp_filter_object_list( $new_ft_feature_shadow_terms, [], 'and', 'slug' ) );

		// 5. restore_current_blog();
		\restore_current_blog();

		return [
			Taxonomies\Taxonomy__ft_level_shadow::NAME => $new_ft_level_shadow_term->slug,
			Taxonomies\Taxonomy__ft_feature_shadow::NAME => $new_ft_feature_shadow_term_slugs,
		];
	}
	 */

	/**
	 * Callback for Sync\AutoDistribute
	 *
	 * @uses Post_Type__CanBeAutoDistributed__Interface
	 * 
	 * @param  [type] $new_post_args [description]
	 * @param  [type] $post          [description]
	 * @param  [type] $args          [description]
	 * @param  [type] $connection       [description]
	 * 
	 * @return [type]                [description]
	 */
	public function on_auto_distribute ( array $new_post_args, \WP_Post $post, array $args, Connections\NetworkSiteConnection $connection ) : array
	{


		if ( $this::NAME !== $post->post_type )
			return $new_post_args;
		/*
		// set author to machine user
		$new_post_args['post_author'] = Users\ft_bot::id();

		// by default 'Distributor' sets the current date as new published_date
		$new_post_args['post_date'] 			= $post->post_date;
		// ..and all related dates ...
		$new_post_args['post_date_gmt'] 		= $post->post_date_gmt;
		$new_post_args['post_modified'] 		= $post->post_modified;
		$new_post_args['post_modified_gmt'] 	= $post->post_modified_gmt;
		*/
		return $new_post_args;
	}






	/**
	 * Because this post_type is non-public and has rewrite set to false,
	 * the user will have no native way of editing the post_name aka permalink aka slug,
	 * which is ok for the design of this special post_type.
	 *
	 * But for the use on the main portal, we have 'ft_site_shadow' as a shadow-taxonomy running, 
	 * which in deed needs a nice term-slug, but shouldn't use its own UI to create it, 
	 * because CRUD of thoose terms is fully automated by our TAX_Shadow-Class.
	 *
	 * VERSION 1:
	 * So, long story short, we want the term-slugs to match the site-title of our websites in the network.
	 *
	 * To achieve this, everytime the site-title is changed and saved,
	 * the post_name field of the updated 'ft_site' post will be set to be empty,
	 * which calls for WP fallback handling of the permalink 
	 * and it will be created from the new given site- aka post-title.
	 *
	 * VERSION 2:
	 * 
	 * 
	 * @param  Array  $data                [description]
	 * @param  Array  $postarr             [description]
	 * @param  Array  $unsanitized_postarr [description]
	 * 
	 * @return [type]                      [description]
	 */
	public function reset_permalink_to_post_title( array $data, array $postarr, array $unsanitized_postarr ) : array 
	{
		// only allow 'publish', 'draft', 'future'
		if ($data['post_type'] !== $this::NAME || $data['post_status'] === 'auto-draft' )
			return $data;

		// run only on updates, 
		// not the very first creation
		if ( empty( $postarr['ID'] ) )
			return $data;

		// only run if something changed
		// if ( $data['post_title'] === \get_post( $postarr['ID'] )->post_title )
			// return $data;
		// $data['post_name'] = $this->get_cleaned_site_url(); //

		//
		/*
		$data['post_name'] = \wp_unique_post_slug( 
			\sanitize_title( $data['post_title'] ),
			$postarr['ID'],
			$data['post_status'],
			$data['post_type'],
			$data['post_parent'] 
		);*/

		// Allow the change of 'post_name' if site_url changes
		if ( \did_filter( 'pre_update_siteurl' ) || \did_filter( 'pre_update_home' )  ) {
			return $data;
		}
		
		// Reset to previous state, in all other cases
		if ( $data['post_name'] !== $postarr['post_name'] ) {
			$data['post_name'] = $postarr['post_name'];
		}

		return $data;
	}

	/**
	 * Fires after the value of a specific option has been successfully updated.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * @since 2.0.1
	 * @since 4.4.0 The `$option` parameter was added.
	 *
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 * @param string $option    Option name.
	 */
	public function sync_options_to_post( mixed $old_value, mixed $value, string $option ) {

		// if ( \current_action('ft_install_defaults') ) {
		if ( empty(\Figuren_Theater\FT::site()->get_site_post_id()) ) {
	/*
	//
	error_log(__FILE__);
	error_log(__FUNCTION__);
	error_log("#######    EMPTY( get_site_post_id )");
	error_log(var_export([$option,$old_value, $value],true ) );
*/
			return;
		}

		//
		$_ft_geo_tax_name = Taxonomies\Taxonomy__ft_geolocation::NAME;

		switch ( $option ) {
			case 'blogname':
				self::update_ft_site_post(
					$value,
					'post_title'
				);
				break;

			case 'blogdescription':
				self::update_ft_site_post(
					$value,
					'post_excerpt'
				);
				break;

			case 'site_icon':
				self::update_ft_site_post(
					[
						'_thumbnail_id' => $value
					],
					'meta_input'
				);
				break;

			case "default_{$_ft_geo_tax_name}_terms":
				self::update_ft_site_post(
					[
						$_ft_geo_tax_name => $value
					],
					'tax_input'
				);				
				break;

			// changing one of theese options
			// should be the only allowed way of 
			// changing 'post_name' of a 'ft_site' post
			case 'siteurl':
			case 'home':
				self::update_ft_site_post( 
					self::get_cleaned_site_url( $value ),
					'post_name'
				);
				break;
			
			default:
				# code...
				break;
		}
	}

	public static function update_ft_site_post( mixed $new_value, string $post_field ) : void {

		// 1. get local site-ID by jsut asking our App, the easyiest of the following ...
		$ft_site_id = (int) \Figuren_Theater\FT::site()->get_site_post_id();

	error_log(__FILE__);
	error_log(__FUNCTION__);
	error_log(\wp_debug_backtrace_summary('WP_Hook'));
		error_log("... is processing >>".$post_field."<< with: ");
		error_log(var_export($new_value,true ) );
		error_log("get_current_blog_id()" );
		error_log(var_export(\get_current_blog_id(),true ) );
		error_log("ft_site_id");
		error_log(var_export($ft_site_id,true) );
		error_log("\n\n");

		$wp_insert_post_data = array_merge(
			[
				'ID' => $ft_site_id,
			],
			[
				$post_field => $new_value,
			],
		);

		// feels weird,
		// but is important!
		// 
		// In ..\plugins\distributor\includes\classes\InternalConnections\NetworkSiteConnection.php
		// around the lines 717ff the 'update_syndicated()' action would be short circuited,
		// because our request is coming on an unusual path.
		// 
		// Luckily there is exactly one filter on the path 
		// to prevent this behaviour and run the full function.
		\add_filter( 'use_block_editor_for_post', '__return_false' );

		// 
		\wp_update_post( $wp_insert_post_data );
	}

	protected function prepare_labels() : array
	{
		return $this->labels = array(

			# Override the base names used for labels:
			'singular' => __('Website','figurentheater'),
			'plural'   => __('Websites','figurentheater'),
			'slug'     => '', // must be string, so an empty one

		);
	}

	protected function register_post_type__default_args() : array
	{
		return array(
			// 'capability_type'     => 'post',
			'capability_type'     => ['ft_site','ft_sites'],
			'supports'            => array(
				'title',
				'editor',
				'author', // needed for long-term management of ft_site, so we have a clear 'owner'
				'thumbnail',
				'excerpt',
				'custom-fields',
				// 'trackbacks',
				// 'comments',
				// 'revisions',
				// 'page-attributes',
				// 'post-formats',
				// 'ft_pending_bubbles', // this is only super_admin relevant, so kick it
			),

			'menu_icon'           => 'dashicons-rest-api',

			'show_ui'             => \current_user_can( 'manage_sites' ),

			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			// 'public'              => true,  // enables editable post_name, called 'permalink|slug'
			'public'              => false,

			'publicly_queryable'  => false,  // was TRUE for long, lets see
			// 'query_var'           => false, // If false, a post type cannot be loaded at ?{query_var}={post_slug}.
			'query_var'           => true, // If false, a post type cannot be loaded at ?{query_var}={post_slug}.

			'show_in_rest'        => true, // this in combination with  'supports' => array('editor') enables the Gutenberg editor
			'hierarchical'        => false, // that to FALSE if not really needed, for performance reasons 
			'description'         => '',
			'taxonomies'          => [
				Features\UtilityFeaturesManager::TAX,
				Taxonomies\Taxonomy__ft_geolocation::NAME,
				Taxonomies\Taxonomy__ft_level_shadow::NAME,
				Taxonomies\Taxonomy__ft_feature_shadow::NAME,
			],

			# disabled for this 'hidden' post_type
			// 'rewrite' => true,  // enables editable post_name, called 'permalink|slug'
			'rewrite' => false,

			#
			'has_archive' => false,

			#
			'can_export' => \current_user_can( 'manage_sites' ),



			# fallback
			'label'         => $this->labels['plural'],

			'template'      => '',
			'template_lock'      => '',
		);
	}

	protected function register_extended_post_type__args() : array
	{
		return array(

			# The "Featured Image" text used in various places 
			# in the admin area can be replaced with 
			# a more appropriate name for the featured image
			'featured_image' => __('Site Icon','figurentheater'),

			#
			'enter_title_here' => __('Site Title','figurentheater'),

			#
			'quick_edit' => true,

			# Add the post type to the site's main RSS feed:
			'show_in_feed' => false,

			# Add the post type to the 'Recently Published' section of the dashboard:
			'dashboard_activity' => false,

			# An entry is added to the "At a Glance" 
			# dashboard widget for your post type by default.
			'dashboard_glance' => false,

			# Add some custom columns to the admin screen:
			'admin_cols' => [
				// A featured image column:
				// 'featured_image' => [
				// 	'title'          => 'Site Icon',
				// 	'function' => function() {},
				// ],
				// A featured image column:
				//'site_icon' => [
				//	'title'          => __('Site Icon','figurentheater'),
				//	'function' => function() {
				//		printf('<img height="32" width="32" src="%s" />',get_site_icon_url( 32, '', get_post_meta( get_the_ID(), '_ft_blog_id', true ) ) );
				//	},
				//],
				
				// The default Title column:
				'title',
				'post_excerpt' => [
					'post_field' => 'post_excerpt',
					'title'      => __('blog description'),
				],
				'post_name' => [
					'post_field' => 'post_name',
					'title'      => __('URL Slug','figurentheater'),
				],

				Taxonomies\Taxonomy__ft_geolocation::NAME => [
					'taxonomy' => Taxonomies\Taxonomy__ft_geolocation::NAME
				],
				Taxonomies\Taxonomy__ft_feature_shadow::NAME => [
					'taxonomy' => Taxonomies\Taxonomy__ft_feature_shadow::NAME
				],
				//	Features\UtilityFeaturesManager::TAX => [
				//		'taxonomy' => Features\UtilityFeaturesManager::TAX,
				//		'title'      => 'UtilityFeatures',
				//	],
				Taxonomies\Taxonomy__ft_level_shadow::NAME => [
					'taxonomy' => Taxonomies\Taxonomy__ft_level_shadow::NAME
				],
				//'published'  => [
				//	'title'      => 'Registered',
				//		'title_icon' => 'dashicons-calendar-alt',
				//	'post_field' => 'post_date',
				//	'default'    => 'DESC',
				//	'date_format' => 'd.m.Y G:i',
				//],
				'last_modified'  => [
					'title'      => __('Last Modified','figurentheater'),
					// 'title_icon' => 'dashicons-calendar-alt',
					'post_field' => 'post_modified',
					'date_format' => 'd.m.Y G:i',
				],
			],

			# Add some dropdown filters to the admin screen:
			'admin_filters' => [
				'ft_site_location' => [
					'title'    => __('🗺️ All Locations','figurentheater'),
					'taxonomy' => Taxonomies\Taxonomy__ft_geolocation::NAME
				],
				'ft_site_feature' => [
					'title'    => __('All Features','figurentheater'),
					'taxonomy' => Taxonomies\Taxonomy__ft_feature_shadow::NAME
				],
				// 'ft_site_utilityfeature' => [
				// 	'title'    => __('All Utility Features','figurentheater'),
				// 	'taxonomy' => Features\UtilityFeaturesManager::TAX
				// ],
				'ft_site_level' => [
					'title'    => __('All Level','figurentheater'),
					'taxonomy' => Taxonomies\Taxonomy__ft_level_shadow::NAME
				],
			],

		);
	}


	public static function get_instance()
	{
		if ( null === self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
}































// add_action( 'admin_init', __NAMESPACE__.'\\debug_Post_Type__ft_site');
#debug_Post_Type__ft_site();


function debug_Post_Type__ft_site(){

$blog_id = get_current_blog_id();

#$new = new Post_Type__ft_site();
$current = new Post_Type__ft_site( 
	get_current_blog_id(), 
	\WP_Site::get_instance( $blog_id ), 
	[
		'user_id' => 1,
		'ft_level' => '793'
	]
);
#$current_wp_site = new Post_Type__ft_site( $blog_id, \WP_Site::get_instance( $blog_id ) );

#$current->prepare_new_ft_level_relative_data( 242 ); // UR web

	wp_die(
		'<pre>'.
		var_export(
			array(
				__FILE__,
#$new,
#\Figuren_Theater\FT::site()->UtilityFeaturesManager,
#$current->get_post_data(),
#$current->get_post_meta(),
$current->get_post_tax(),
#$current->prepare_new_ft_level_relative_data( 242 ), // UR web
#$current_wp_site,
#				FT::site()->FeaturesManager,
			),
			true
		).
		'</pre>'
	);
}
