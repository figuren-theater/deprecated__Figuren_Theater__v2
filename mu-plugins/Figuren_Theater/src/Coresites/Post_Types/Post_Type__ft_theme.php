<?php 
declare(strict_types=1);

namespace Figuren_Theater\Coresites\Post_Types;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types as Network_Post_Types;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\Network\Users;
use Figuren_Theater\SiteParts;

use function tlc_transient;

/**
 * Responsible for registering the 'ft_theme' post_type 
 */
class Post_Type__ft_theme extends Network_Post_Types\Post_Type__Abstract implements Network_Post_Types\Post_Type__CanCreatePosts__Interface, EventManager\SubscriberInterface, SiteParts\Data__CanAddYoastTitles__Interface
{

	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_theme';

	const SLUG = 'themes'; // NOT USED, YET // post_type archive slug

	protected $theme = null;    // WP_Theme object

	/**
	 * Used when 'ft_theme' posts
	 * are created or updated.
	 * 
	 * @var boolean
	 */
	protected $update_ft_theme = false;

	/**
	 * The Class Object
	 */
	static private $instance = null;


	function __construct( \WP_Theme $theme = null )
	{
		if (null !== $theme )
			$this->theme = $theme;
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
			// Filters slashed post data just before it is inserted into the database.
			'wp_insert_post_data' => [ 'prevent_slug_change_for_file_themes', 10, 3 ],

			'save_post_' . static::NAME => [ 'save_themes_api_data', 10, 3 ],

			// update post_meta from themes API using soft-expiration
			// use this hook, because it is sure to be triggered on every real frontend request, 
			// not on ajax, not for error-handlers and it is the last possible 
			// without the need for authentication and 
			// before 'shutdown' is called, 
			// which is triggerd way to often for this use-case
			'wp_print_footer_scripts' => ['update_themes_api_data', 100 ]

			// 'init' => ['debug', 42],
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
	 * @see       https://trello.com/c/D7lFumgs/137-yoast-seo 
	 * @see       https://yoast.com/help/list-available-snippet-variables-yoast-seo/
	 *
	 * @package POST_TYPE__FT_PRODUCTION
	 * @version 2022.04.15
	 * @author  Carsten Bach
	 *
	 * @example for post_types   
		return = [
			'title'                        => '%%title%% %%page%% %%sep%% %%sitename%%',
			'metadesc'                     => '%%excerpt%%',
			'display-metabox'              => true,  // show some metabox for this data
			'noindex'                      => false, // prevent robots indexing
			'maintax'                      => 0,
			'schema-page-type'             => 'WebPage',
			'schema-article-type'          => 'None',
			'social-title'                 => '%%title%% %%sep%% %%sitename%%',
			'social-description'           => '%%excerpt%%',
			'social-image-url'             => '',
			'social-image-id'              => 0,
			'title-ptarchive'              => '%%archive_title%% %%page%% %%sep%% %%sitename%%',
			'metadesc-ptarchive'           => '',
			'bctitle-ptarchive'            => '', // no replacement of yoast variables like  %%title%%
			'noindex-ptarchive'            => false,
			'social-title-ptarchive'       => '%%archive_title%% %%sep%% %%sitename%%',
			'social-description-ptarchive' => '',
			'social-image-url-ptarchive'   => '',
			'social-image-id-ptarchive'    => 0,
		];
	 *
	 *
	 * @return  Array       list of 'wpseo_titles' definitions 
	 *                      for this posttype or taxonomy
	 */
	public static function get_wpseo_titles() : array
	{
		return [
			'title'                        => '%%title%% %%page%% %%sep%% %%category%% %%sep%% %%pt_single%% %%sep%% %%sitename%%',
			'metadesc'                     => '%%excerpt%%',
			
			'maintax'                      => 'category',

			'schema-article-type'          => 'TechArticle',
			// 'bctitle-ptarchive'            => 'BCTITLE %%title%% %%parent_title%% %%ft_parent_pt%%', // no replacement of yoast variables like  %%title%%


			// NOT WORKING
			// 'social-title'                 => '%%title%% %%sep%% %%pt_single%% %%sep%% %%sitename%%',
			// 'twitter-title'                 => '%%title%% %%sep%% %%pt_single%% %%sep%% %%sitename%%',

		];
	}



	/**
	 * Get the post data as a wp_insert_post compatible array.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_insert_post/
	 *
	 * @return array
	 */
	public function get_post_data() : array
	{
		return [
			'post_title'     => $this->theme->__get('title'),
			'post_name'      => $this->theme->stylesheet, // slug
			'post_excerpt'   => $this->theme->display( 'Description' ), // 
			'post_status'    => 'draft', // start with draft, switch to publish on later point
			'post_type'      => self::NAME,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_author'    => Users\ft_bot::id(),
			// 'meta_input' => $this->get_post_meta(),
		];
	}

	/**
	 * Get all the post meta as a key-value associative array.
	 *
	 * @return array
	 */
	public function get_post_meta() : array
	{
		return [];
	}


	public function set_post_thumbnail( int $post_id ) : bool
	{
		// check if screenshot exists
		$filetype = 'png';
		$file = join('/', [
			$this->theme->theme_root,
			$this->theme->stylesheet,
			'screenshot.png'
		] );
		if ( ! file_exists( $file ))
		{
			$filetype = 'jpg';
			// 2nd try
			// check if screenshot exists
			$file = join('/', [
				$this->theme->theme_root,
				$this->theme->stylesheet,
				'screenshot.jpg'
			] );
			if ( ! file_exists( $file ))
				return false;
		}
	

		// \do_action( 'qm/debug', $file );
		// \do_action( 'qm/debug', file_exists( $file ) );

		// STOP
		// return false;

		// copy to uploads folder and create image_sizes
		$filename = join('-', [
			'theme',
			$this->theme->stylesheet,
			'screenshot.' . $filetype
		] );
		/*
		 * $file is the path to your uploaded file (for example as set in the $_FILE posted file array)
		 * $filename is the name of the file
		 * first we need to upload the file into the wp upload folder.
		*/
		$upload_file = \wp_upload_bits( $filename, null, @file_get_contents( $file ) );
		
		// if not succesfull, bail
		if ( $upload_file['error'] )
			return false;
		// \do_action( 'qm/debug', $upload_file );

		// if succesfull 
		// insert the new file into the media library 
		// (create a new attachment post type).
		$wp_filetype = \wp_check_filetype($filename, null );

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_parent'    => $post_id,
			// 'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_title'     => $this->theme->__get('title') . ' Screenshot',
			'post_content'   => '',
			'post_status'    => 'inherit',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_author'    => Users\ft_bot::id(),
			'meta_input'=> [
				'_wp_attachment_image_alt' => sprintf(
					__('Screenshot des %1$s WordPress Theme, vorbereitet & getestet für Deine neue Theater-Homepage.','figurentheater'),
					$this->theme->__get('title')
				),
			]
		);
		// \do_action( 'qm/debug', $attachment );

		$attachment_id = \wp_insert_attachment( $attachment, $upload_file['file'], $post_id );

		// \do_action( 'qm/debug', $attachment_id );

		// something went wrong
		if ( \is_wp_error( $attachment_id ) )
			return false;
	
		// if attachment post was successfully created, 
		// insert it as a thumbnail to the post $post_id.
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');

		$attachment_data = \wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );

		\wp_update_attachment_metadata( $attachment_id,  $attachment_data );
		\set_post_thumbnail( $post_id, $attachment_id );

		// 
		return true;
	}

	 
	/**
	 * Filters slashed post data just before it is inserted into the database.
	 *
	 * @see https://wp-kama.com/hook/wp_insert_post_data 
	 * 
	 * @param  [type] $data                [description]
	 * @param  [type] $postarr             [description]
	 * @param  [type] $unsanitized_postarr [description]
	 * @return [type]                      [description]
	 */
	public function prevent_slug_change_for_file_themes( $data, $postarr, $unsanitized_postarr ) : array
	{
		if ( self::NAME !== $data['post_type'] )
			return $data;

		// bail, if not an update, so, might be the 'first publish'
		if ( ! isset( $postarr['ID'] ) || ! intval( $postarr['ID'] ) > 0 )
			return $data;

		// only act on auto-created themes from files
		// which are created by the 'ft_bot' machine-user
		$_ft_bot_id = Users\ft_bot::id();

		//if ( $data[ 'post_author' ] === $_ft_bot_id; )
		//	return $data;

		// 
		$ft_query = \Figuren_Theater\FT_Query::init();
		$_original = $ft_query->find_one( array( 
			'p' => $postarr['ID'], 
			'post_status' => 'any',
			'post_type' => self::NAME,
		) );

		//
		if (null === $_original)
			return $data;

		if ( intval( $_original->post_author ) === $_ft_bot_id ) {

			// make sure this is kept 
			$data[ 'post_author' ] = $_ft_bot_id;
			$data[ 'post_name' ] = $_original->post_name; // set in files
		}

		return $data;
	}

	/**
	 * Runs on every public, non-ajax request
	 * and triggers the update process 
	 * for the data coming from the WordPress themes API 
	 * and is going to (aka is saved as) post_meta.
	 *
	 * @since   3.0
	 */
	public function update_themes_api_data() : void {
		if ( ! \is_singular( self::NAME ) )
			return;


		$post = \get_post();

		if ( ! is_a( $post, 'WP_POST' ) )
			return;

		// echo "Hello ". __FUNCTION__;

		tlc_transient( 'wporg-theme-' . $post->ID )
			->updates_with( 
				// [$this,'save_themes_api_data'],
				__NAMESPACE__ . '\\ft_trigger_themes_api_update',
				[
					$post->ID,
				]
			)
			->expires_in( \WEEK_IN_SECONDS )
			->get();
			// ->background_only() // not working, as expected
			// ->expires_in( 60 )
	}


	/**
	 * Get and persist Data from the WordPress themes-API,
	 * when posts are created and updated.
	 * - ratings
	 * - downloads
	 *
	 * @since   3.0
	 */
	public function save_themes_api_data( int $post_ID, \WP_Post $post, bool $update ) : void {
/*error_log(var_export([
	__FUNCTION__,
	$post_ID, 
	$post,
	$update,
	$themes_api_data
],true));*/
		if ( \wp_is_post_revision( $post ))
			return;

		// use TLC transients here
		
		$this->update_ft_theme = $update;

		$themes_api_data = $this->get_themes_api_data( $post->post_name );
/*error_log(var_export([
	__FUNCTION__,
	$post_ID, 
	$post,
	$update,
	$themes_api_data
],true));*/
		if ( empty( $themes_api_data ) )
			return;

		if ( $this->update_ft_theme && ! empty( $post->post_content ) ) {
			$this->update_ft_theme_meta( $post_ID, $themes_api_data );
		} else {
			$this->set_ft_theme_content( $post_ID, $themes_api_data );
		}

		
	}

	public function get_themes_api_data( string $slug ) : array {
		$raw = $this->get_raw_themes_api_data( $slug );
		if ( \is_wp_error( $raw ) )
			return [];

		// cast as array, 
		// as it could be either an array or an object
		$raw = (array) $raw;

/*error_log(var_export([
	__FUNCTION__,
	$slug, 
	$raw
],true));		*/	
		return array_combine(
			// array_keys( $this->get_themes_api_fields() ), 
			[
				// 'screenshots',
				'ratings',
				'rating',
				'num_ratings',
				'downloaded',
				'description',
			],
			[
				// $raw['screenshots'],
				$raw['ratings'],
				$raw['rating'],
				$raw['num_ratings'],
				$raw['downloaded'],
				$raw['sections']['description'],
			]
		);
		// return $raw;
	}

	/**
	 * [get_raw_themes_api_data description]
	 *
	 * @package [package]
	 * @since   3.0
	 *
	 * @param  string $slug Slug of theme we want information of.
	 * @return object|array|WP_Error Response object or array on success, WP_Error on failure. See the
	 *         {@link https://developer.wordpress.org/reference/functions/themes_api/ function reference article}
	 *         for more information on the make-up of possible return objects depending on the value of `$action`.
	 */
	public function get_raw_themes_api_data( string $slug ) : object|array {
		if ( ! function_exists( 'themes_api' ) ) {
			require ABSPATH . 'wp-admin/includes/theme.php';
		}

		return \themes_api( 
			'theme_information',
			[
				'slug'   => $slug,
				'locale' => 'de_DE',
				'fields' => $this->get_themes_api_fields(),
			]
		);
	}

	/**
	 * Returns all the fields, to get from the WP themes API.
	 *
	 * @since   3.0
	 *
	 * @return  array    List of fields.
	 */
	protected function get_themes_api_fields() : array {

		$fields = [
			// Whether to return the theme full description. Default false.
			'description' => true,
			// Whether to return the screenshots. Default false.
			// 'screenshots' => true,
			// Whether to return the rating in percent and total number of ratings.
			'rating'      => true,
			// Whether to return the number of rating for each star (1-5). Default false.
			'ratings'     => true,
			// Whether to return the download count. Default false.
			'downloaded'  => true,
		];

		// the 'ft_theme' post is created right now
		return $fields;
	}

	public function set_ft_theme_content( int $post_ID, array $data ) : void {
/*error_log(var_export([
	__FUNCTION__,
	$post_ID, 
	$data
],true));	*/	
		if ( ! isset($data['description']) || empty( $data['description'] ) )
			return;

		// remove filter hook, to prevent infinite loops
		\remove_filter( 'save_post_' . static::NAME, [ $this, 'save_themes_api_data' ] );

		// update post content
		\wp_update_post( 
			[
				'ID'           => $post_ID,
				'post_content' => $data['description'],
				'post_excerpt' => '',
			]
		);

		//
		$this->update_ft_theme_meta( $post_ID, $data );
		
		// re-add filter
		\add_filter( 'save_post_' . static::NAME, [ $this, 'save_themes_api_data' ], 10, 3 );
	}

	public function update_ft_theme_meta( int $post_ID, array $data ) : void {
/*error_log(var_export([
	__FUNCTION__,
	$post_ID, 
	$data
],true));	*/
		if ( empty( $data ) )
			return;

		// 
		unset( $data['description']);
		// unset( $data['screenshots']);

		// $api_fields = array_keys( $this->get_themes_api_fields() );
		$api_fields = array_keys( $data );
		// update post meta
		array_walk(
			$api_fields,
			function ( $meta_key ) use ( $post_ID, $data ) {
				\update_post_meta( 
					$post_ID,
					$meta_key,
					$data[ $meta_key ],
				);
			}
		);
		
	}

	protected function prepare_pt() : void
	{
	}

	protected function prepare_labels() : array
	{
		return $this->labels = array(

			# Override the base names used for labels:
			'singular' => _x('Theme','singular post_type label','figurentheater'),
			'plural'   => _x('Themes','plural post_type label','figurentheater'),
			'slug'     => $this::SLUG,

		);
	}

	protected function register_post_type__default_args() : array
	{
		return array(
			// 'capability_type'     => ['ft_site','ft_sites'],
			// 'capability_type'     => 'ft_theme', fallback to default 'post'
			'supports'            => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				// 'trackbacks',
				// 'comments',
				// 'revisions',
				// 'page-attributes',
				// 'post-formats',
				'ft_pending_bubbles',
				// 'ft_sub_title',
			),

			'menu_icon'           => 'dashicons-admin-appearance',

			'show_in_rest'        => true, // this in combination with  'supports' => array('editor') enables the Gutenberg editor

			'hierarchical'        => false, // that to FALSE if not really needed, for performance reasons 

			'taxonomies'          => [
				// 'ft_product', // keep till migrated // old connection from ft_SALES Plugin, not used anymore in 2021 data-scheme
				// Taxonomies\Taxonomy__ft_level_shadow::NAME,
				// 'ft_milestone',
		
				Features\UtilityFeaturesManager::TAX,

				'post_tag',
				'category'
			],

			// 'has_archive'           => $this::SLUG,
			// 'has_archive'           => false,
			'has_archive'           => true,
			'rewrite'               => [
				'slug'                  => $this::SLUG,
				'with_front'            => false,
				'pages'                 => false,
				'feeds'                 => false,
				'hierarchical'          => false,

			],

			# fallback
			'label'         => $this->labels['plural'],
			'template' => array(
				array( 'core/pattern', array(
					'slug' => 'figurentheater/hidden-ft_theme-header',
				) ),
			),
		);
	}



	protected function register_extended_post_type__args() : array
	{
		return array(

			# The "Featured Image" text used in various places 
			# in the admin area can be replaced with 
			# a more appropriate name for the themed image
			'featured_image' => __('Theme Screenshot','figurentheater'),

			#
			'enter_title_here' => __('Theme Title','figurentheater'),

			#
			'quick_edit' => true,

			# Add the post type to the site's main RSS feed:
			'show_in_feed' => false,

			# Add the post type to the 'Recently Published' section of the dashboard:
			'dashboard_activity' => true,

			# An entry is added to the "At a Glance" 
			# dashboard widget for your post type by default.
			// 'dashboard_glance' => false,


			/**
			 * CAUTION 
			 *
			 * Do not add custom columns 
			 * via CPT Extended if you use a
			 * 
			 * 'hierachical' => TRUE post_type
			 *
			 * The default WP UI for parent/child post relationship 
			 * is somehow destroyed by the custom columns of the plugin.
			 *
			 * So beware.
			 */ // END CAUTION for 'hierachical' => TRUE post_type
			# Add some custom columns to the admin screen:
			'admin_cols' => [
				// A themed image column:
				//'theme_screenshot' => [
				//	'title'          => _x( 'Screenshot', 'ft_theme image', 'figurentheater' ),
				//	'featured_image' => 'thumbnail',
				//	'width'          => 80,
				//	'height'         => 80,
				//],
				// The default Title column:
				#'title',
				// 'featured_image',

				// A meta field column:
				'demo' => array(
					// 'title'       => __('Downloaded', 'figurentheater'),
					'title_icon'  => 'dashicons-cover-image',
					// 'meta_key'    => 'published_date',
					// 'meta_key'    => 'downloaded',
					// 'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
					// 'sortable'  => true,
					'function' => function() {
						global $post;
						if ( \has_term( 'ft_theme-has-demo-site', 'hm-utility', $post ) ) {
							$demo_link = sprintf(
								'<a href="%s" rel="external" target="_blank"><span class="dashicons dashicons-cover-image"></span><span class="screen-reader-text">Demo</span></a>',
								\site_url( 'demos/' . $post->post_name . '/' )
							);
							echo $demo_link;
						}
					},
				),

				// A meta field column:
				'registration' => array(
					// 'title'       => __('Downloaded', 'figurentheater'),
					'title_icon'  => 'dashicons-forms',
					// 'meta_key'    => 'published_date',
					// 'meta_key'    => 'downloaded',
					// 'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
					// 'sortable'  => true,
					'function' => function() {
						global $post;
						
						if ('publish' !== $post->post_status) {
							return;
						}

						if ( \has_term( 'ft_theme-register-with', 'hm-utility', $post ) ) {
							echo '<span class="dashicons dashicons-forms" style="color:darkgreen;"></span><span class="screen-reader-text">Can register with</span>';
						} else {
							echo '<span class="dashicons dashicons-dismiss" style="color:#888;"></span><span class="screen-reader-text">Not prepared</span>';
						}
					},
				),
				// A meta field column:
				'downloaded' => array(
					// 'title'       => __('Downloaded', 'figurentheater'),
					'title_icon'  => 'dashicons-download',
					// 'meta_key'    => 'published_date',
					// 'meta_key'    => 'downloaded',
					// 'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
					// 'sortable'  => true,
					'function' => function() {
						global $post;
						echo \number_format_i18n( floatval( $post->downloaded ) );
						// echo floatval( $post->downloaded );
					},
				),

				// A meta field column:
				'rating' => array(
					// 'title'       => __('Rating', 'figurentheater'),
					'title_icon'  => 'dashicons-star-half',
					// 'meta_key'    => 'published_date',
					// 'meta_key'    => 'rating',
					'function' => function() {
						global $post;

						$rating   = get_post_meta(
							$post->ID,
							'rating',
							true
						);

						if ( ! $rating )
							return;

						// calc $rating 
						// which is based on 100
						$calc = intval( $rating / 20 );
						// 
						// example
						// (86) / 20 = 4,3
						// 
						// prepare for viewing
						$readable_rating = sprintf(
							_x('%s','amount of stars per user rating','figurentheater'),
							str_repeat('⭐', $calc)
						);
						echo $readable_rating;
					},
					// 'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
				),

	#			'categories',
	#			'tags',
				//'ft_milestone' => [
				//	'taxonomy' => 'ft_milestone',
				//	'title'    => __('Milestones','figurentheater'),
				//],

				// 'comments',

				//'published'  => [
				//	'title'      => 'Registered',
				//	// 'title_icon' => 'dashicons-calendar-alt',
				//	'post_field' => 'post_date',
				//	'default'    => 'DESC',
				//	'date_format' => 'd.m.Y G:i',
				//],
				//'last_modified'  => [
				//	'title'      => 'Last Modified',
				//	// 'title_icon' => 'dashicons-calendar-alt',
				//	'post_field' => 'post_modified',
				//	'date_format' => 'd.m.Y G:i',
				//],
			],

			# Add some dropdown filters to the admin screen:
			'admin_filters' => [
				'ft_theme_tag' => [
					'title'    => __('All Tags','figurentheater'),
					'taxonomy' => 'post_tag'
				],
				//'ft_theme_milestone' => [
				//	'title'    => __('All Milestones','figurentheater'),
				//	'taxonomy' => 'ft_milestone'
				//],
			],

		);
	}



	public static function get_instance()
	{
		if ( null === self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	public function debug()	{
		// \do_action( 'qm/emergency', \get_post_type_object( static::NAME ) );
		// \do_action( 'qm/emergency', static::get_raw_themes_api_data( 'oaknut' ) );

	}
}

/**
 * Helper function
 * to use with 
 * tlc-transients lib
 *
 * Which saves its callbacks to the DB,
 * and we do not want the whole class inside the DB, multiple times.
 *
 * so this is the fast & dirty solution.
 *
 * @since   3.0
 *
 * @return  [type]    [description]
 */
function ft_trigger_themes_api_update (  int $post_ID  ) {
	$class = Post_Type__ft_theme::get_instance();
	$class->save_themes_api_data( $post_ID, \get_post( $post_ID ), true );
/*
error_log(var_export([
	__FUNCTION__,
	$post_ID, 
	$post,
	$class
],true));*/

	return true;
}
