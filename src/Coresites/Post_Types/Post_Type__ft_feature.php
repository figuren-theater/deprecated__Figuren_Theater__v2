<?php 
declare(strict_types=1);

namespace Figuren_Theater\Coresites\Post_Types;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Post_Types as Network_Post_Types;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\Network\Users;
use Figuren_Theater\SiteParts;


/**
 * Responsible for registering the 'ft_feature' post_type 
 */
class Post_Type__ft_feature extends Network_Post_Types\Post_Type__Abstract implements Network_Post_Types\Post_Type__CanCreatePosts__Interface, EventManager\SubscriberInterface, SiteParts\Data__CanAddYoastTitles__Interface
{

	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_feature';

	const SLUG = 'features'; // post_type archive slug

	protected $feature_slug = ''; // post_type singular slug

	/**
	 * The Class Object
	 */
	static private $instance = null;


	function __construct( String $feature_slug = '' )
	{
		if (!empty($feature_slug)) {
			$this->feature_slug = $feature_slug;
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
			'wp_insert_post_data' => [ 'prevent_slug_change_for_file_features', 10, 3 ],
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
			'metadesc'                     => '%%excerpt%% %%sep%% %%ct_ft_level_shadow%%',
			
			'maintax'                      => 'category',
			// 'maintax'                      => 'ft_level_shadow',

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
			'post_title'     => $this->feature_slug,
			'post_name'      => $this->feature_slug, // slug
			'post_status'    => 'draft', // start with draft, switch to publish on later point
			'post_type'      => self::NAME,
			'comment_status' => 'open',
			'ping_status'    => 'open',
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
	public function prevent_slug_change_for_file_features( $data, $postarr, $unsanitized_postarr ) : array
	{
		if ( self::NAME !== $data['post_type'] )
			return $data;

		// bail, if not an update, so, might be the 'first publish'
		if ( ! isset( $postarr['ID'] ) || ! intval( $postarr['ID'] ) > 0 )
			return $data;

		// only act on auto-created features from files
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


	protected function prepare_pt() : void
	{
	}

	protected function prepare_labels() : array
	{
		return $this->labels = array(

			# Override the base names used for labels:
			'singular' => _x('Feature','singular post_type label','figurentheater'),
			'plural'   => _x('Features','plural post_type label','figurentheater'),
			'slug'     => $this::SLUG,

		);
	}

	protected function register_post_type__default_args() : array
	{
		return array(
			// 'capability_type'     => ['ft_site','ft_sites'],
			// 'capability_type'     => 'ft_feature', fallback to default 'post'
			'supports'            => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'trackbacks',
				'comments',
				// 'revisions',
				'page-attributes',
				// 'post-formats',
				'ft_pending_bubbles',
				'ft_sub_title',
			),

			'menu_icon'           => 'dashicons-forms',

			'show_in_rest'        => true, // this in combination with  'supports' => array('editor') enables the Gutenberg editor

			'hierarchical'        => true, // that to FALSE if not really needed, for performance reasons 

			'taxonomies'          => [
				'ft_product', // keep till migrated // old connection from ft_SALES Plugin, not used anymore in 2021 data-scheme
				// Taxonomies\Taxonomy__ft_level_shadow::NAME, // not needed // because ft_level's have ft_feature_shadow's, not the other way around
				'ft_milestone',
		
				'post_tag',
				'category'
			],

			'has_archive'           => $this::SLUG,
			'rewrite'               => [
				'slug'                  => $this::SLUG,
				'with_front'            => true,
				'pages'                 => true,
				'feeds'                 => true,
				'hierarchical'          => true,

			],

			# fallback
			'label'         => $this->labels['plural'],
			'template' => array(
				array( 'core/pattern', array(
					'slug' => 'figurentheater/hidden-ft_feature-header',
				) ),
			),
		);
	}



	protected function register_extended_post_type__args() : array
	{
		return array(

			# The "Featured Image" text used in various places 
			# in the admin area can be replaced with 
			# a more appropriate name for the featured image
			// 'featured_image' => __('Site Icon','figurentheater'),

			#
			'enter_title_here' => __('Feature Title','figurentheater'),

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
				// A featured image column:
				'featured_image' => [
					'title'          => _x( 'Image', 'ft_feature image', 'figurentheater' ),
					'featured_image' => 'thumbnail',
					'width'          => 80,
					'height'         => 80,
				],
				// The default Title column:
				#'title',

				'categories',
				'tags',
				'ft_milestone' => [
					'taxonomy' => 'ft_milestone',
					'title'    => __('Milestones','figurentheater'),
				],

				'comments',

			#	'published'  => [
			#		'title'      => 'Registered',
			#		// 'title_icon' => 'dashicons-calendar-alt',
			#		'post_field' => 'post_date',
			#		'default'    => 'DESC',
			#		'date_format' => 'd.m.Y G:i',
			#	],
			#	'last_modified'  => [
			#		'title'      => 'Last Modified',
			#		// 'title_icon' => 'dashicons-calendar-alt',
			#		'post_field' => 'post_modified',
			#		'date_format' => 'd.m.Y G:i',
			#	],
			],

			# Add some dropdown filters to the admin screen:
			'admin_filters' => [
				'ft_feature_tag' => [
					'title'    => __('All Tags','figurentheater'),
					'taxonomy' => 'post_tag'
				],
				'ft_feature_milestone' => [
					'title'    => __('All Milestones','figurentheater'),
					'taxonomy' => 'ft_milestone'
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
