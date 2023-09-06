<?php 
declare(strict_types=1);

namespace Figuren_Theater\Coresites\Post_Types;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Post_Types as Network_Post_Types;
use Figuren_Theater\Network\Taxonomies;

/**
 * Site-Levels available on the plattform figuren.theater 
 * similar to what where the pro-sites Levels on WPMUDEV
 */
/**
 * Responsible for registering the 'ft_level' post_type 
 */
class Post_Type__ft_level extends Network_Post_Types\Post_Type__Abstract implements EventManager\SubscriberInterface
{

	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_level';

	const SLUG = 'level';


	/**
	 * The Class Object
	 */
	static private $instance = null;



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

	/**
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
	 */
	public static function prepare_new_ft_level_relative_data( int $new_ft_level ) : array
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


	protected function prepare_pt() : void {}

	protected function prepare_labels() : array
	{
		return $this->labels = array(

			# Override the base names used for labels:
			'singular' => _x('Level','singular post_type label','figurentheater'),
			'plural'   => _x('Level','plural post_type label','figurentheater'),
			'slug'     => $this::SLUG,

		);
	}

	protected function register_post_type__default_args() : array
	{
		return array(
			'capability_type'     => 'ft_level',
			// 'capability_type'     => ['ft_site','ft_sites'],
			'supports'            => array(
				'title',
				'editor',
				// 'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				// 'trackbacks',
				// 'comments',
				// 'revisions',
				// 'page-attributes',
				// 'post-formats',
				'ft_pending_bubbles',
				'ft_sub_title',
			),

			'menu_icon'           => 'dashicons-cart',

			'show_in_rest'        => true, // this in combination with  'supports' => array('editor') enables the Gutenberg editor
			'hierarchical'        => false, // that to FALSE if not really needed, for performance reasons 

			'taxonomies'          => [
				Taxonomies\Taxonomy__ft_feature_shadow::NAME // this is set also in UtilityFeature__core__sitemanagement, but maybe twice is good|ok
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

		);
	}



	protected function register_extended_post_type__args() : array
	{
		return array(

			# The "Featured Image" text used in various places 
			# in the admin area can be replaced with 
			# a more appropriate name for the featured image
			'featured_image' => __('Level Icon','figurentheater'),

			#
			'enter_title_here' => __('Level Title','figurentheater'),

			#
			'quick_edit' => true,

			# Add the post type to the site's main RSS feed:
			'show_in_feed' => false,

			# Add the post type to the 'Recently Published' section of the dashboard:
			'dashboard_activity' => true,

			# An entry is added to the "At a Glance" 
			# dashboard widget for your post type by default.
			// 'dashboard_glance' => false,

			# Add some custom columns to the admin screen:
			'admin_cols' => [
				// A featured image column:
					'featured_image' => [
						'title'          => __('Level Icon','figurentheater'),
						'featured_image' => 'thumbnail'
					],
				// The default Title column:
				'title',
				// 'post_name' => [
				// 	'title'      => 'Slug',
				// 	'post_field' => 'post_name',
				// ],
				Taxonomies\Taxonomy__ft_feature_shadow::NAME => [
					'taxonomy' => Taxonomies\Taxonomy__ft_feature_shadow::NAME // TODO // should be the shadow-taxonomy of PT 'ft_feature'
				],
				'published'  => [
					'title'      => __('Registered','figurentheater'),
					// 'title_icon' => 'dashicons-calendar-alt',
					'post_field' => 'post_date',
					'default'    => 'DESC',
					'date_format' => 'd.m.Y G:i',
				],
				'last_modified'  => [
					'title'      => __('Last Modified','figurentheater'),
					// 'title_icon' => 'dashicons-calendar-alt',
					'post_field' => 'post_modified',
					'date_format' => 'd.m.Y G:i',
				],

			],

			# Add some dropdown filters to the admin screen:
			'admin_filters' => [
				Taxonomies\Taxonomy__ft_feature_shadow::NAME => [
					'title'    => __('Features','figurentheater'),
					'taxonomy' => Taxonomies\Taxonomy__ft_feature_shadow::NAME
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
