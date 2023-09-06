<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Sync;
use Figuren_Theater\Network\Taxonomies;

use Distributor\InternalConnections as Connections;

/**
 * 
 */
class Feature__sync__figuren_theater extends Features\Feature__Abstract implements Post_Types\Post_Type__CanBeAutoDistributed__Interface
{

	const SLUG = 'sync-figuren-theater';

	/**
	 * [$site_id description]
	 * 
	 * @var integer
	 */
	private $site_id = 0;

	function __construct()
	{
		//
		$_coresites = array_flip( FT_CORESITES );
		$this->site_id = $_coresites['root'];	 
	}


	public function enable() : void 
	{
		// MAYBETODO
		// find a better place to call thoose default business-logic
		// UPDATE
		// why not, after some vice-versa-testing - I think this is a nice spot
		// UPDATE 2 // TODO
		// this should go into siteSetup and gets called once from there,
		// after the initial setup the default plugin takes care of the work
		// 
		// THIS IS TRICK, BUGGY, whatever ...
		// moved this call around for a while,
		// hooked it onto 'init', 'admin_menu' and 'admin_init'
		// with no success
		// 
		// Moved this away from ft_site PT 
		// to keep things flexible 
		// using our created features_PT for such things
		// 
		// BUT this (init|0) works now :()
		new Sync\AutoDistribute( Post_Types\Post_Type__ft_site::NAME, $this->site_id ); // https://figuren.theater

		/**
		 * gets called on '{new_post_status}_{post_type}' hook,
		 * inside of wp_transition_post_status()
		 * fyi, {new_post_status} defaults to 'publish'.
		 */
		new Sync\AutoDistribute( 'post', $this->site_id, [$this,'on_auto_distribute'] );

		new Sync\AutoDistribute( Post_Types\Post_Type__ft_production::NAME, $this->site_id, [$this,'on_auto_distribute'] ); // https://figuren.theater


		// before new post is inserted into receiving site
		\add_filter( 'dt_syncable_taxonomies', [$this,'dont_distribute_categories_on_posts'], 10, 2 );

		// after new post is inserted into receiving site
		\add_action( 'dt_push_post', [$this,'add_szene_cat_to_new_posts'], 10, 4 );
		\add_action( 'dt_push_post', [$this,'add_ft_production_shadow_terms_on_new_posts'], 10, 4 );
	}


	public function enable__on_admin() : void {

		global $pagenow;
		if (
			( 'options-general.php' === $pagenow )
			&&
			( isset( $_GET['page'] ) )
			&&
			( 'impressum' === $_GET['page'] )
		)
		{
			$this->conditional_load();
			// \add_action( 'current_screen', [ $this, 'conditional_load'], 1 );
		}
	}


	/**
	 * Add terms of this taxonomy to every auto-distributed post from the network
	 * maybe this is not the right spot, ...
	 * but: 
	 * 1. having this per auto-distributed post_type will be not DRY
	 * 2. having this on the taxonomy itself could be ok, but this creates such a tight coupling between 
	 *    our infrastructure plugin 'Distributor' and the taxonomy
	 * 3. having it here, feels best ... lets see   
	 * 
	 */
	/**
	 * Filter the arguments sent to the remote server during a push.
	 * 
	 * Must be a public function, because it 
	 * applies the filtering on 'dt_push_post_args', 
	 * which is modifier aka filter for the syndicated post.
	 * The autodsitributed post will be created new with the given arguments.
	 * 
	 * @since 1.0
	 * @hook dt_push_post_args
	 *
	 * @param  {array}                 $new_post_args  wp_insert_post()-compatible array of post-arguments
	 * @param  {object}                $post           The WP_Post that is being pushed.
	 * @param  {array}                 $args           Post args to push.
	 * @param  {NetworkSiteConnection} $this           The distributor connection being pushed to.
	 *
	 * @return {array} The request body to send.
	 */
	public function on_auto_distribute( array $new_post_args, \WP_Post $post, array $args, Connections\NetworkSiteConnection $connection ) : array
	{
		// Use 'error_log' inside this crazy context ...
		// Example:
		// error_log(var_export(['on_auto_distribute__post'=>[$new_post_args, $post, $args, $connection]], true));

		if ( 
			'post' !== $post->post_type
			&&
			Post_Types\Post_Type__ft_production::NAME !== $post->post_type
		)
			return $new_post_args;


		// Jump out if first run
		// this sound crazy, but the exact same function will run in a second 
		// again, triggered by an 'Distributor'-action called "update_syndicated"
		// hooked on "save_post" and this second run, will know the ID
		if (!isset($new_post_args['ID']))
			return $new_post_args;

		// 0. we are on the RECEIVING end of the connection with NO knowledge about the SENDING site
		// switch back to SENDING, to get some knowledge
		\restore_current_blog();

		// 1. get local 'ft_site' post-ID by jsut asking our App, the easyiest of the following ...
		$sending__ft_site_id = \Figuren_Theater\FT::site()->get_site_post_id();
		// bail 
		if ( \is_wp_error( $sending__ft_site_id ) || ! is_int( $sending__ft_site_id )) 
		{
			// back to RECEIVING site
			\switch_to_blog( $connection->site->blog_id );
			return $new_post_args;
		}

		//
		$connection_map = \get_post_meta( 
			$sending__ft_site_id, 
			'dt_connection_map', 
			true 
		);

		// back to RECEIVING site
		\switch_to_blog( $connection->site->blog_id );


		if ( empty( $connection_map ) || ! is_array( $connection_map ) || empty( $connection_map['internal'] ) || ! isset( $connection_map['internal'][ (int) $connection->site->blog_id ]['post_id'] ) ) {
			return $new_post_args;
		} else {
			$receiving__ft_site__id = (int) $connection_map['internal'][ (int) $connection->site->blog_id ]['post_id'];
		}


		// 2. get connected 'ft_site_shadow'-term-id
		$TAX_Shadow = Taxonomies\TAX_Shadow::init();
		$receiving__ft_site_term = $TAX_Shadow->get_associated_term( 
			$receiving__ft_site__id, 
			Taxonomies\Taxonomy__ft_site_shadow::NAME 
		);

		if ( !$receiving__ft_site_term instanceof \WP_Term )
			return $new_post_args;

		// FINALLY
		// set term to connect this post with its original site 
		// Avoid permission checks 
		// on current_user_can( $taxonomy_obj->cap->assign_terms )
		$Taxonomy__ft_site_shadow = \wp_set_object_terms( 
			$new_post_args['ID'], 
			[$receiving__ft_site_term->slug],
			Taxonomies\Taxonomy__ft_site_shadow::NAME
		);

		return $new_post_args;
	}



	/**
	 * Filters the taxonomies that should be synced.
	 *
	 * @since 1.0
	 * @hook dt_syncable_taxonomies
	 *
	 * @param {array}  $taxonomies  Associative array list of taxonomies supported by current post in the format of `$taxonomy => $terms`.
	 * @param {WP_Post} $post       The post object.
	 *
	 * @return {array} Associative array list of taxonomies supported by current post in the format of `$taxonomy => $terms`.
	 */
	public function dont_distribute_categories_on_posts( array $taxonomies, \WP_Post $post ) : array
	{
		// $post_taxonomies = \get_object_taxonomies( $post );
		// unset($post_taxonomies['category']);
		// return $post_taxonomies;
		return array_diff( $taxonomies, [ 'category' ] );
	}


	/**
	 * Fires after a post is pushed via Distributor before `restore_current_blog()`.
	 *wp_insert_post
	 * @hook dt_push_post
	 *
	 * @param {int}        $new_post_id   The newly created post ID.
	 * @param {int}        $post_id       The original post ID.
	 * @param {array}      $args          The arguments passed into the connection.
	 * @param {Connection} $this          The Distributor connection being pushed to.
	 */
	public function add_szene_cat_to_new_posts( Int $new_post_id, Int $post_id, Array $args, Connections\NetworkSiteConnection $connection )
	{
		// WHY is this disabled ??
		//  if ( 'post' !== \get_post_type( $new_post_id ) )
		// 	return; // early

		$Taxonomy__category = \wp_set_object_terms( 
			(int) $new_post_id, 
			['szene'],
			'category'
			// true # append ? 
		);
	}


	/**
	 * Fires after a post is pushed via Distributor before `restore_current_blog()`.
	 *wp_insert_post
	 * @hook dt_push_post
	 *
	 * @param {int}        $new_post_id   The newly created post ID.
	 * @param {int}        $post_id       The original post ID.
	 * @param {array}      $args          The arguments passed into the connection.
	 * @param {Connection} $this          The Distributor connection being pushed to.
	 */
	public function add_ft_production_shadow_terms_on_new_posts( Int $new_post_id, Int $post_id, Array $args, Connections\NetworkSiteConnection $connection )
	{
		if ( Post_Types\Post_Type__ft_production::NAME === \get_post_type( $new_post_id ) )
			return; // early

		$_dt_original_blog_id = (int) \get_post_meta( $new_post_id, 'dt_original_blog_id', true );


		if ( 0 === $_dt_original_blog_id )
			return;

		\switch_to_blog( $_dt_original_blog_id );

		$_original_ft_production_shadows = \wp_get_post_terms( $post_id, Taxonomies\Taxonomy__ft_production_shadow::NAME, array( 'fields' => 'slugs' ) );


		// counterpart is defined in
		// Post_Types\Post_Type__ft_production->on_auto_distribute()
		$_new_ft_production_shadows = array_map(
			function( $slug ) use ( $_dt_original_blog_id )
			{
				return $slug . '___' . $_dt_original_blog_id;
			},
			$_original_ft_production_shadows
		);
		\restore_current_blog();


		$Taxonomy__ft_production_shadow = \wp_set_object_terms( 
			(int) $new_post_id, 
			$_new_ft_production_shadows,
			Taxonomies\Taxonomy__ft_production_shadow::NAME
			// true # append ? 
		);


		/*
		#wp_die('we are here');
			?>
			<script>
				console.log(<?php echo json_encode(array( $_original_ft_production_shadows)); ?>);
				console.log(<?php echo json_encode(array( $_new_ft_production_shadows ) ); ?>);
				console.log(<?php echo json_encode( $Taxonomy__ft_production_shadow ); ?>);
				console.log(<?php echo json_encode( $_dt_original_blog_id); ?>);
				console.log(<?php echo json_encode(array( $new_post_id, $post_id, $args)); ?>);
			</script>
			<?php*/
	}


	/**
	 * Load by 'current_screen' usage
	 * to make sure DB query against 'ft_geo'
	 * only runs on this particular admin-page
	 * 
	 *
	 * @package project_name
	 * @version version
	 * @author  Carsten Bach
	 *
	 * @param   \WP_Screen   $current_screen [description]
	 * 
	 * @return  none
	 */
	// public function conditional_load( \WP_Screen $current_screen )
		// guard
		// if ( $this->related_screen_ids[0] !== $current_screen->base )
			// return;
	public function conditional_load() {

		$ft_geo = \get_option( 'ft_geo','' );
		if ( empty($ft_geo) || ! isset($ft_geo['tax_terms']) || empty($ft_geo['tax_terms']) )
			return;

		// start the engines
		$Admin_UICollection = \Figuren_Theater\API::get('Admin_UI');

		// 
		if ( ! \Figuren_Theater\FT::site()->has_feature(['sync-figuren-theater']) ) {
			return;
		}
	
		$notice = new Admin_UI\Rule__will_add_admin_notice( 
			'publish_posts', // user_capability
			// $this->related_screen_ids, // screen_ID
			'settings_page_impressum',
			new Admin_UI\Admin_Notice(
				sprintf( $this->site_address__as_termlinks__for_admin_notice( $ft_geo ) ),
				'is-dismissible info'
			)
		);
		$Admin_UICollection->add( $this::SLUG . '__ft_geoadmin_notice', $notice);
	}


	public function site_address__as_termlinks__for_admin_notice( array $ft_geo ) {

		$_ft_append_desc = '';
		$_term_links = array();

		// we only have thoose taxonomy-terms on the main f.t
		\switch_to_blog( $this->site_id );

		foreach ($ft_geo['tax_terms'] as $tax_term_id ) {
			$_term = \get_term( $tax_term_id, Taxonomies\Taxonomy__ft_geolocation::NAME );
			$_term_link = \get_term_link( $_term, Taxonomies\Taxonomy__ft_geolocation::NAME );

			if ( $_term && $_term_link ) {
				$_term_links[] = sprintf(
					'<a target="_blank" href="%s">%s</a>',
					esc_url( $_term_link ),
					esc_html( $_term->name )
				);
			}
		}

		$_site = \get_site_url( $this->site_id );
		$_site_title = \esc_html( \get_bloginfo( 'name' ) );

		// fallback, just in case
		$_promoting_site = 'figuren.theater';

		if ( $_site && $_site_title ) {
			$_promoting_site = sprintf(
				'<a target="_blank" href="%s">%s</a>',
				esc_url( $_site ),
				esc_html( $_site_title )
			);
		}

		//
		\restore_current_blog();

		$_ft_append_desc = sprintf(
			'%s: %s',
			sprintf(
				_x('%s will promote your content for','%s is a Link to the promoting site','figurentheater'),
				$_promoting_site
			),
			implode(', ', $_term_links)
		);

		return $_ft_append_desc;
	}



}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
