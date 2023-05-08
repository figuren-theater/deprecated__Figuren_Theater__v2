<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\SiteParts;

use Distributor\InternalConnections as Connections;


/**
 * Responsible for registering the 'ft_production' post_type 
 */

/**
 * We need this post_type on every site,
 * 
 * This post_type is created and maintained from 
 * a user-site e.g. https://abc.figuren.theater
 * should be automatically distributed to the main portal https://figuren.theater.
 * 
 */
class Post_Type__ft_production extends Post_Type__Abstract implements Post_Type__CanBeAutoDistributed__Interface, EventManager\SubscriberInterface, SiteParts\Data__CanAddYoastTitles__Interface
{

	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_production';

	const SLUG = 'produktionen';


	const PREMIERE_META_KEY = '_theatre_base_prod_and_event__premiere';

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

			//
			'pre_get_posts' => 'default_order_by_premiere',

			// 'wp_loaded' => 'debug',
		);
	}

	/**
	 * [default_order_productions_by_premiere description]
	 * Order Events by start date
	 * 
	 * @see https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
	 * 
	 * For date format yyyy-mm-dd hh:mm:ss
	 * $args = array(
	 *   'post_type'      => 'event',
	 *   'post_status'    => 'publish',
	 *   'meta_key'       => 'start',
	 *   'meta_type'      => 'DATETIME',
	 *   'orderby'        => 'meta_value',
	 *   'order'          => 'ASC',
	 * );
	 * In this case orderby does not require meta_value_datetime.
	 * @see https://developer.wordpress.org/reference/classes/wp_query/#comment-5309
	 *
	 * @package project_name
	 * @version version
	 * @author  Carsten Bach
	 *
	 * @param   \WP_Query    $query [description]
	 */
	public static function default_order_by_premiere( \WP_Query $query )
	{

		if ( \is_admin() )
			return;

		// Not a query for an admin page.
		
		if ( !isset( $query->query_vars['post_type']) )
			return;

		// query with more than one post_type
		// could not be ours
		// bail out
		if ( 1 < count( (array) $query->query_vars['post_type'] ) )
			return;

		// we can asume query_vars['post_type']
		// is a string
		if( self::NAME !== $query->query_vars['post_type'] )
			return;

		// three scenarios
		// 0. queried exact 1 production from within a tb_prod_subsite
		// 1. 'orderby' is unset, so we just setup our query-vars
		// 2. 'orderby' is set to date, which will be hitchhiked and overwritten
global $post;
		
		if (
			false === $query->is_main_query()
			#&&
			#is_singular( 'tb_prod_subsite' )
			&&
			is_a( $post, 'WP_Post' )
			&&
			'tb_prod_subsite' === $post->post_type
			// &&
			// self::NAME === $query->query_vars['post_type']
			&&
			1 === $query->query_vars['posts_per_page']
			#&&
			#1 === $query->query_vars['posts_per_page']
			&&
			null !== $parent = \get_post_parent() 
		) {
			// unset( $query->query_vars['order'] );
			// $query->set( 'order',   false );
#			// unset( $query->query_vars['orderby'] );
			// $query->set( 'orderby',   false );
#			// unset( $query->query_vars['post_type'] );
			// $query->set( 'post_type',   false );
#			// unset( $query->query_vars['posts_per_page'] );
			// $query->set( 'posts_per_page',   false );
#			$query->set( 'is_archive',   false );
#			$query->set( 'is_singular',   true );
#
	#		$query->parse_query([
	#			'p' => $parent->ID,
	#			'post__in' => array( $parent->ID ),
	#		]);
			// $query->set( 'p',   $parent->ID );
			// $query->set( 'post__in',  array( $parent->ID ) );
			// $query->set( 's',  $parent->post_title );

			// $query->parse_query( [ 'p' => $parent->ID ] );
			// $query->parse_query( [ 'post__in' => [ $parent->ID ] ] );

	// \do_action( 'qm/debug', $query );
		// die('<pre>'.var_export([\get_post_parent()->ID,$query], true).'</pre>');
			// return;
		}


		else if ( 
			// 1. 'orderby' is unset
			! isset( $query->query_vars['orderby'])
			||
			// we can asume it is set, so we can ask for
			// 2. 'orderby' is set to date
			'date' === $query->query_vars['orderby']
		)
		{
			// ok now we are looking for the correct queries
			$query->set( 'meta_key',  self::PREMIERE_META_KEY );
			$query->set( 'meta_type', 'DATETIME' );
			$query->set( 'orderby',   'meta_value' );

			// in case we have no order set
			// define a sense-making default and
			// show the least current production first
			if ( ! isset( $query->query_vars['order']) )
			{
				$query->set( 'order',     'DESC');
			}

		}
		// die(var_dump( $query->query_vars));
		#	$query->set( 'meta_query', array(
		#		array(
		#			'key'     => 'event_date',
		#			'compare' => '>=',
		#			'value'   => date('Ymd'),
		#			'type'    => 'numeric',
		#		)
		#	) );
		// die(var_dump( $query->query_vars));

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
			'title'                        => '%%title%% %%page%% %%sep%% %%pt_single%% %%sep%% %%ct_ft_site_shadow%% %%sep%% %%sitename%%',
			// 'display-metabox'              => false,  // show some metabox for this data

			// 'bctitle-ptarchive'            => 'BCTITLE %%title%% %%parent_title%% %%ft_parent_pt%%', // no replacement of yoast variables like  %%title%%


			// NOT WORKING
			// 'social-title'                 => '%%title%% %%sep%% %%pt_single%% %%sep%% %%sitename%%',
			// 'twitter-title'                 => '%%title%% %%sep%% %%pt_single%% %%sep%% %%sitename%%',

		];
	}


	/**
	 * Callback for Sync\AutoDistribute
	 *
	 * Primary purpose is to add the sending 
	 * Site-ID to the permalink of the 
	 * new created production-post.
	 *
	 * This is called on the RECEIVING end 
	 * of the connection with NO knowledge 
	 * about the SENDING site.
	 *
	 * @package Figuren_Theater
	 * @version 
	 * @author  Carsten Bach
	 *
	 * @param   array                             $new_post_args Arguments to be used with wp_insert_post()
	 * @param   WP_Post                           $post          The local post to distribute
	 * @param   array                             $args          [description]
	 * @param   Connections\NetworkSiteConnection $connection    Distributor Connection Object of remote site
	 * 
	 * @return  array                                            [description]
	 */
	public function on_auto_distribute ( array $new_post_args, \WP_Post $post, array $args, Connections\NetworkSiteConnection $connection ) : array
	{


		// wp_die('we are here');
		// error_log(var_export(array( __CLASS__,$new_post_args, $post, $args, $connection ), true ));
		/*	?>
			<script>
				console.log(<?php echo json_encode(array( __CLASS__,$new_post_args, $post, $args, $connection )); ?>);
			</script>
			<?php*/

		if ( $this::NAME !== $post->post_type )
			return $new_post_args;

		// 0. we are on the RECEIVING end of the connection with NO knowledge about the SENDING site
		// switch back to SENDING, to get some knowledge
		\restore_current_blog();

		// 1. get local site-ID by jsut asking our App, the easyiest of the following ...
		// $sending__site_id = \Figuren_Theater\FT::site()->get_site_id( 0 );
		// --> error prone
		$sending__site_id = \get_current_blog_id();
		

		// bail 
		if ( \is_wp_error( $sending__site_id ) || ! is_int( $sending__site_id )) 
		{
			// back to RECEIVING site
			\switch_to_blog( $connection->site->blog_id );
			return $new_post_args;
		}

		// back to RECEIVING site
		\switch_to_blog( $connection->site->blog_id );

		// we needed a way to decide between 17 variants of 'Faust'
		// which would end in 17 posts with the same slug and upcounting numbers
		// now we have the same, but with site IDs
		// this maybe a security problem
		$new_post_args['post_name'] = $post->post_name . '___' . $sending__site_id;

		// ready to create a new post from that
		return $new_post_args;
	}






	protected function prepare_pt() : void
	{


		// TODO // temp. handled by 
		// '...\plugins\theatrebase-production-subsites\theatrebase-production-subsites.php'
		
		/*
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( 
			new Post_TypesTemplateLoader( 
				[
					'blank.php' => _x('Blank', 'Template Title', 'theatrebase-production-subsites')
				], 
				// \plugin_dir_path( __FILE__ ) . 'templates/', 
				// TODO
				\plugin_dir_path( ) . 'templates/', 
				self::NAME 
			) 
		);*/
	}


	protected function prepare_labels() : array
	{
		return $this->labels = array(

			# Override the base names used for labels:
			'singular' => __('Production', 'singular post_type label', 'figurentheater'),
			'plural'   => __('Productions', 'plural post_type label', 'figurentheater'),
			'slug'     => $this::SLUG, // must be string

		);
	}

	protected function register_post_type__default_args() : array
	{
		return array(
			'capability_type'     => 'post',
			// 'capability_type'     => ['ft_production','ft_productions'],
			'supports'            => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'trackbacks',
				'comments',
				'revisions',
				// 'page-attributes',
				'ft_pending_bubbles',
				'ft_sub_title',
			),

			'menu_icon'           => 'dashicons-art',

			// 'show_ui'             => true,

			// 'show_in_menu'       => false,
			// 'show_in_nav_menus'   => false,
			// 'show_in_admin_bar'   => false,
			// 'public'              => false,
			// 'public'              => true,  // enables editable post_name, called 'permalink|slug'
			'public'              => false, // will be enabled on init|5 by 'Feature__produktionen'

			// 'publicly_queryable'  => false,  // was TRUE for long, lets see
			// 'query_var'           => false, // If false, a post type cannot be loaded at ?{query_var}={post_slug}.

			'show_in_rest'        => true, // this in combination with  'supports' => array('editor') enables the Gutenberg editor
			'hierarchical'        => true, // that to FALSE if not really needed, for performance reasons 
			'description'         => '',
			'taxonomies'          => [
				Features\UtilityFeaturesManager::TAX,
				Taxonomies\Taxonomy__ft_geolocation::NAME, # must be here to allow setting its terms, even when hidden
				Taxonomies\Taxonomy__ft_site_shadow::NAME, # must be here to allow setting its terms, even when hidden
				// Taxonomies\Taxonomy__ft_season::NAME,
				// Taxonomies\Taxonomy__ft_az-index::NAME,
				'post_tag',
				// 'category',
			],

			// 'rewrite' => true,  // enables editable post_name, called 'permalink|slug'
			'rewrite' => false, // will be enabled on init|5 by 'Feature__produktionen'

			#
			// 'has_archive' => true,
			'has_archive' => false, // will be enabled on init|5 by 'Feature__produktionen'

			#
			// 'can_export' => \current_user_can( 'manage_sites' ),



			/**
			 * Localiced Labels
			 * 
			 * ExtendedCPTs generates the default labels in English for your post type. 
			 * If you need to allow your post type labels to be localized, 
			 * then you must explicitly provide all of the labels (in the labels parameter) 
			 * so the strings can be translated. There is no shortcut for this.
			 *
			 * @source https://github.com/johnbillion/extended-cpts/pull/5#issuecomment-33756474
			 * @see https://github.com/johnbillion/extended-cpts/blob/d6d83bb41eba9a3603929244c71f3f806c2a14d8/src/PostType.php#L152
			 */
			# fallback
			'label'                 => $this->labels['plural'],
			'labels'                => [
				'name'                     => __( 'Productions', 'figurentheater' ),
				'singular_name'            => __( 'Production', 'figurentheater' ),
				'add_new'                  => __( 'Add New', 'figurentheater' ),
				'add_new_item'             => __( 'Add New Production', 'figurentheater' ),
				'edit_item'                => __( 'Edit Production', 'figurentheater' ),
				'new_item'                 => __( 'New Production', 'figurentheater' ),
				'view_item'                => __( 'View Production', 'figurentheater' ),
				'view_items'               => __( 'View Productions', 'figurentheater' ),
				'search_items'             => __( 'Search Productions', 'figurentheater' ),
				'not_found'                => __( 'No productions found.', 'figurentheater' ),
				'not_found_in_trash'       => __( 'No productions found in trash.', 'figurentheater' ),
				'parent_item_colon'        => __( 'Parent Production:', 'figurentheater' ),
				'all_items'                => __( 'All Productions', 'figurentheater' ),
				'archives'                 => __( 'Production Archives', 'figurentheater' ),
				'attributes'               => __( 'Production Attributes', 'figurentheater' ),
				'insert_into_item'         => __( 'Insert into production', 'figurentheater' ),
				'uploaded_to_this_item'    => __( 'Uploaded to this production', 'figurentheater' ),
				'featured_image'           => _x( 'Image', 'ft_production image', 'figurentheater' ),
				'set_featured_image'       => _x( 'Set image', 'ft_production image', 'figurentheater' ),
				'remove_featured_image'    => _x( 'Remove image', 'ft_production image', 'figurentheater' ),
				'use_featured_image'       => _x( 'Use as image', 'ft_production image', 'figurentheater' ),
				'filter_items_list'        => __( 'Filter productions list', 'figurentheater' ),
				'filter_by_date'           => __( 'Filter by date', 'figurentheater' ),
				'items_list_navigation'    => __( 'Productions list navigation', 'figurentheater' ),
				'items_list'               => __( 'Productions list', 'figurentheater' ),
				'item_published'           => __( 'Production published.', 'figurentheater' ),
				'item_published_privately' => __( 'Production published privately.', 'figurentheater' ),
				'item_reverted_to_draft'   => __( 'Production reverted to draft.', 'figurentheater' ),
				'item_scheduled'           => __( 'Production scheduled.', 'figurentheater' ),
				'item_updated'             => __( 'Production updated.', 'figurentheater' ),
				'item_link'                => __( 'Production Link', 'figurentheater' ),
				'item_link_description'    => __( 'A link to a production.', 'figurentheater' ),
				'menu_name'                => __( 'Productions', 'figurentheater' ),
				'name_admin_bar'           => __( 'Production', 'figurentheater' ),
			],
			'template'      => '',
			'template_lock'      => '',
		);
	}


	protected function register_extended_post_type__args() : array
	{
		$_date_format = \get_option('date_format');
		return array(

			# The "Featured Image" text used in various places 
			# in the admin area can be replaced with 
			# a more appropriate name for the featured image
			'featured_image' => _x( 'Image', 'ft_production image', 'figurentheater' ),

			#
			'enter_title_here' => __('Production Title','figurentheater'),

			#
			'quick_edit' => true,

			# Add the post type to the site's main RSS feed:
			// 'show_in_feed' => true,
			'show_in_feed' => false, // will be enabled on init|5 by 'Feature__produktionen'

			# Add the post type to the 'Recently Published' section of the dashboard:
			// 'dashboard_activity' => true,
			'dashboard_activity' => false, // will be enabled on init|5 by 'Feature__produktionen'

			# An entry is added to the "At a Glance" 
			# dashboard widget for your post type by default.
			'dashboard_glance' => false, // will be enabled on init|5 by 'Feature__produktionen'

			# Add some custom columns to the admin screen:
			'admin_cols' => [
				// // A featured image column:
				// 'featured_image' => [
				// 	'title'          => _x( 'Image', 'ft_production image', 'figurentheater' ),
				// 	'featured_image' => 'thumbnail',
				// 	'width'          => 80,
				// 	'height'         => 80,
				// ],
				// The default Title column:
				'title',
				// A meta field column:
				'premiere' => array(
					'title'       => __('Premiere', 'figurentheater'),
					// 'title_icon'  => 'dashicons-calendar-alt',
					// 'meta_key'    => 'published_date',
					'meta_key'    => '_theatre_base_prod_and_event__premiere',
					'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
				),
				// A meta field column:
				'duration' => array(
					'title'       => __('Duration', 'figurentheater'),
					// 'title_icon'  => 'dashicons-calendar-alt',
					// 'meta_key'    => 'published_date',
					'meta_key'    => '_theatre_base_prod_and_event__duration',
					// 'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
				),
				// A meta field column:
				'targetgroup' => array(
					'title'       => __('Targetgroup', 'figurentheater'),
					// 'title_icon'  => 'dashicons-calendar-alt',
					// 'meta_key'    => 'published_date',
					'meta_key'    => '_theatre_base_prod_and_event__targetgroup',
					// 'date_format' => $_date_format,
					// Any column can be made the default sort column 
					// (instead of the default Title column) 
					// by using the default parameter 
					// and giving it a value of ASC or DESC
					// 'default'  => 'DESC',
				),
				// maybe later ..
				// Features\UtilityFeaturesManager::TAX => [
				//	'taxonomy' => Features\UtilityFeaturesManager::TAX,
				//	'title'      => 'UtilityFeatures',
				//],
				'post_tag' => [
					'taxonomy' => 'post_tag'
				],
				//
				//'last_modified' => array(
				//	'title'      => _x( 'Last modified', 'ft_production last modified time', 'figurentheater' ),
				//	'post_field' => 'post_modified',
				//	'default'  => 'DESC',
				//),
			],

			# Add some dropdown filters to the admin screen:
			'admin_filters' => [
				// maybe later ..
				//'ft_production_utilityfeature' => [
				//	'title'    => 'All Utility Features',
				//	'taxonomy' => Features\UtilityFeaturesManager::TAX
				//],
				'post_tag' => [
					'title'    => __('All Tags', 'figurentheater'),
					'taxonomy' => 'post_tag'
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


	public function debug(){

		\wp_die(
			'<pre>'.
			var_export(
				array(
					__FILE__,
					// $this,
					// \get_option( 'wpseo_titles' ),
					\get_post_type_labels( \get_post_type_object( self::NAME ) ), 
				),
				true
			).
			'</pre>'
		);
	}
}
