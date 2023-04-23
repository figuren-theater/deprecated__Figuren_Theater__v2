<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Sync;
// use Figuren_Theater\FeaturesRepo;
use Figuren_Theater\Network\Post_Types;

use Distributor\InternalConnections;

/**
 * Auto Distribute some posts on sudden conditions using the glorious
 * 'Distributor' Plugin by 10up
 *
 * heavily based on
 * @see https://github.com/10up/distributor/blob/develop/includes/classes/InternalConnections/NetworkSiteConnection.php#L65-L178 InternalConnections\NetworkSiteConnection\push()
 * and
 * @see https://github.com/10up/distributor/blob/faadb682cc1d1bc9037c445a46d86aa7949e6109/includes/push-ui.php#L224-L374 PushUI\ajax_push()
 * inspired by some nice issue threads:
 * 
 * @see https://github.com/10up/distributor/issues/76#L224 2020.12.12
 * @see https://github.com/10up/distributor/issues/424#issuecomment-511561107 2019.07.15
 */
class AutoDistribute
{
	private $setup = [];

	private $current_site_id = 0;

	protected $remote_site = 0;

	protected $post_status = '';

	protected $post_type_model = null;

	function __construct( string $post_type, int $remote_site_id = 0, $on_distribute_action = null, string $post_status = 'publish' ) {

		// _doing_it_wrong( $function_name, $message, $version )

		//
		$this->current_site_id = (int) \get_current_blog_id();

		// this whole operation
		// including the DB query within this setup 
		// shouldn't run on every page, explicit on every admin-page.
		// We give everything to a late construcor.
		$this->setup = [
			'post_type' => $post_type,
			'remote_site_id' => $remote_site_id,
			'on_distribute_action' => $on_distribute_action,
			'post_status' => $post_status,
		];


		// \add_action( 'admin_head-edit.php', [$this, 'setup'] ); // missing REST_API init
		// \add_action( 'admin_head-post.php', [$this, 'setup'] ); // missing REST_API init
		// \add_action( 'admin_menu', [$this, 'setup'] ); // BUGGY // Distribution works, but neither it is attached, ft_site->on_auto_distribute() got never called.
		// \add_action( 'admin_init', [$this, 'setup'] ); // BUGGY // Distribution works, but neither it is attached, ft_site->on_auto_distribute() got never called.
		\add_action( 'init', [$this, 'setup'] );  // WORKING 

	}

	public function setup()
	{
		// because this needs - for reasons - to be called on 'init'
		// we make sure, this only happens to logged in users with caps
		// inside the wp-admin
		if ( !\is_admin() || !\current_user_can( 'publish_posts') )
			return;


		if ( \WP_DEBUG && 1===2 ) {
			\do_action( 'qm/info', 'We are on Site "{current}" and want to auto-distribute "{pt}" on "{status}" to Site "{remote}". (This is a WP_DEBUG only message.)', [
				'current' => $this->current_site_id,
				'pt' => $this->setup['post_type'],
				'status' => $this->setup['post_status'],
				'remote'  => $this->setup['remote_site_id'],
			] );
		}

		// will still be 'null', if not available
		$this->post_type_model = \Figuren_Theater\API::get('PT')->get( $this->setup['post_type'] );
		// but maybe we have it in the lib already
		$_class = "\Figuren_Theater\Network\Post_Types\Post_Type__{$this->setup['post_type']}";
		if ( 
			is_null( $this->post_type_model ) 
			&& 
			class_exists( $_class ) 
			&& 
			method_exists( $_class, 'get_instance')
		)
			$this->post_type_model = $_class::get_instance();



		if ( is_null( $this->post_type_model ) )
		{
			\do_action( 'qm/warning', 'Something is wrong with your "{pt}". It\'s neither registered nor part of our collection. Jump out.', [
				'pt' => $this->setup['post_type'],
			] );
			return;
		}




		// 2. we want to act on one of our post_types, 
		// so we can rely on our model and use its methods
		// or
		if ( 
			// ( ! is_null( $this->post_type_model ) )
			// &&
			( ! $this->post_type_model instanceof Post_Types\Post_Type__CanBeAutoDistributed__Interface )
			&&
			( ! is_null( $this->setup['on_distribute_action'] ) )
			&&
			( ! is_callable( $this->setup['on_distribute_action'] ) )
		) 
		{
			\do_action( 'qm/warning', 'The post_type "{pt}" you registered for auto-distribution needs to implement the "{interface}" or you should provide a working (!) callback - right now it is not callable!', [
			// \do_action( 'qm/warning', 'The "{pt}" post_type you registered for auto-distribution can be modeled, but needs to implement the "{interface}".', [
				'pt' => $this->setup['post_type'],
				'interface' => 'Post_Type__CanBeAutoDistributed__Interface',
			] );
			return;
		}


		/*
		// two scenarios
		// 1. we want to act on an foreign post_type, 
		// for what we dont have a proper modell
		// so we need to have at least a proper callback
		// 
		// if nothing is the case with the given properties, 
		// exit and die 
		elseif ( ! is_callable( $this->setup['on_distribute_action'] ) )
		{
			\do_action( 'qm/warning', 'The function "{fn}", you registered as callback for auto-distribution of {pt} can\'t be called.', [
				'fn' => json_encode( $this->setup['on_distribute_action'] ),
				'pt' => $this->setup['post_type'],

			] );
			return;
		}
		*/



		// maybe we should also check for get_current_blog_id();
		// 


		// last checking
		// 
		// DISABLED to save onb DB request
		// and there is no need for the fetched WP_Site-object
		// 
		// 1st TRY
		// ---------
		// THIS WAS SO STUPID
		// IT IS A CLASS !!! HUH!
		// DAMN IDIOT
		// 
		// 2nd TRY
		// ---------
		// do this late
		// to prevent the DB lookups on every page load
		// 
		// $this->remote_site = \get_site( $this->setup['remote_site_id'] );
		// if ( ! $this->remote_site instanceof \WP_Site )
		
		// soft alternative
		if ( 0 === $this->setup['remote_site_id'] || !is_int( $this->setup['remote_site_id'] ) )
		{
			\do_action( 'qm/warning', 'The Site with ID "{id}", you want to auto-distribute to, doesn\'t exist.', [
				'id' => $this->setup['remote_site_id'],
			] );
			return;
		}


		$this->post_status = $this->setup['post_status'];
		if ( 'publish' !== $this->post_status ) 
		{
			// TODO 
			// add registration for other post_statuses
			// with 'Distributor' filter, if needed
			// https://10up.github.io/distributor/dt_distributable_post_statuses.html
			// 
			// for now, 
			// go with an warning ...
			\do_action( 'qm/warning', 'Auto-distribution for a post-status other than "publish", needs some codework at {ns}.', [
				'ns' => __CLASS__,
			] );

			// V1: and reset to 'publish'
			// $this->post_status = 'publish';
			
			// V2: since distributor version XYZ other post_statuses can be distributed, lets see if this works ...
			// $this->post_status = $this->setup['post_status']; 

			// V3: dont let other statuses be distributed
			return;
		}

		// so everything seems to be ok
		// let's go on ... and 


		// 1. register as 'distributable_post_types'
		new AllowDistribution( $this->setup['post_type'] );

		// 2. and define WHEN to jump in
		$add_action_handle = "{$this->post_status}_{$this->setup['post_type']}";

		/**
		 * {status}_{post_type} Hook
		 * This hook is tricky 
		 *
		 * @see https://codex.wordpress.org/Post_Status_Transitions#.7Bstatus.7D_.7Bpost_type.7D_Hook 
		 *
		 * Because this lives inside of wp_insert_post(), 
		 * which is called from within our first_distribution()
		 *
		 * source of wp_insert_post( $postarr, $wp_error );
		 * @see https://wp-kama.com/filecode/wp-includes/post.php#L3663 
		 *
		 * Read on some interesting, related:
		 * @see https://chandrapatel.in/hooks-inside-wp-insert-post-function/
		 * @see https://stackoverflow.com/questions/40783844/infinite-loop-error-with-publish-post-and-wp-insert-post 
		 * @see https://wordpress.stackexchange.com/questions/120996/add-action-only-on-post-publish-not-update 
		 * @see https://wordpress.stackexchange.com/questions/102349/faster-way-to-wp-insert-post-add-post-meta-in-bulk 
		 * @see https://wordpress.stackexchange.com/questions/275634/can-i-trigger-the-publish-post-hook-by-using-wp-insert-post 
		 * 
		 * @var string
		 */
		// register our first-distribution-handler
		\add_action( $add_action_handle, [ $this, 'first_distribution' ], 10, 2 );
/*
error_log(var_export("

	add_action( $add_action_handle, [ $ this, 'first_distribution' ], 10, 2 )",true));
error_log(var_export($this->setup,true));
*/

		/**
		 * set callback for 'dt_push_post_args'
		 * 
		 * adds a filter on wp_insert_post() for that post_type 
		 * to handle authors, meta, terms, etc. on distribution
		 *
		 * Filter documented in 
		 * distributor/includes/classes/ExternalConnections/WordPressExternalConnection.php
		 * 
		 * @var callable
		 */
		// $distribute_action = ( is_callable( $this->setup['on_distribute_action'] ) ) ? $this->setup['on_distribute_action'] : [ $this->post_type_model, 'on_auto_distribute' ];		
		// 1. Run action hooked to the new AutoDistribute call
		if ( is_callable( $this->setup['on_distribute_action'] ) )
			\add_filter( 'dt_push_post_args', $this->setup['on_distribute_action'], 10, 4 );

		// 2. Run action hooked to the post_type by default
		if ( is_callable( [ $this->post_type_model, 'on_auto_distribute' ] ) )
			\add_filter( 'dt_push_post_args', [ $this->post_type_model, 'on_auto_distribute' ], 11, 4 );

	}







	// https://wordpress.stackexchange.com/questions/120996/add-action-only-on-post-publish-not-update
	// called as action, so no need to return anything useful
	public function first_distribution( int $post_id, \WP_Post $post )
	{
		// Use 'error_log' inside this crazy context ...
		// Example:
		// error_log(var_export(['first_distribution'=>[$post_id, $post]], true));
/*
error_log(var_export("

	".__NAMESPACE__ . '\\' . __FUNCTION__ . '\\' . $this->setup['remote_site_id'] ."

	first_distribution of :::: " . $post->post_title,true));

*/
		// only go on, from the primary source
		// YEAH, YEAH, YEAH, I searched more than 2 days to find the correct spot to prevent
		// distribution-chaining
		// caused by the fact, that by using 'switch_to_blog'
		// WP doesn't load or unload the plugins needed for the 
		// switched (to) blog, so we stay with what we had before switching
		// especially in context of activated and deactivated plugins
// @TODO
// THIS prevents ft_site creation during install, as it runs from the main_site_of_the_network
#		if ( \ms_is_switched() )
#			return;
	
		// MAYBE better to check for existing origins
	#	$_dt_original_post_id = \get_post_meta( $post_id, 'dt_original_post_id', true );
	#	if ( $_dt_original_post_id )
	#		return;		
		
	#	// next try to prevent chanining
	#	$_dt_original_blog_id = \get_post_meta( $post_id, 'dt_original_blog_id', true );
	#	if ( $_dt_original_blog_id && \get_current_blog_id() !== $_dt_original_blog_id )
	#		return;

		// prepare late
		// to prevent the DB lookups on every page load
		$this->remote_site = \get_site( $this->setup['remote_site_id'] );
		

		// make sure this is up-to-date
		$this->current_site_id = (int) \get_current_blog_id();


		// jump out if going loops !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// $current_site_id = (int) \get_current_blog_id();
		if ($this->current_site_id === (int) $this->remote_site->blog_id)
			return;


$_once_only_action = __NAMESPACE__ . '\\' . __FUNCTION__ . '\\' . $this->remote_site->blog_id;
if ( \did_action( $_once_only_action ))
	return;
/*
error_log(var_export("

	$_once_only_action

	first_distribution of :::: " . $post->post_title,true));
*/
\do_action( $_once_only_action , $post_id );
			

		// make sure this runs only once,
		// and not on every update, like the called hook
		// is doing.
		// 
		// So not e.g. on 
		// add_action( 'save_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'update_syndicated' ) );
		// which triggers the calling 'publish_ft_site'-action


		// YES it was a big error by design because
		// when this fn was called on "publish_ft_site" or "publish_post"
		// for sure this post already existed.
		// $update is not reliable (in our use-case)
		// because it is only false, when switching from 
		// non-existent post (null) to post_status "auto-draft"
		// all other cases thi is TRUE

		// check if this is for sure the first publishing
		// so we have to rely on the post_meta, 
		// because this will be only their if our fn ran
		$connection_map = \get_post_meta( $post_id, 'dt_connection_map', true );

		if ( empty( $connection_map ) ) {
			$connection_map = array();
		}

		if ( empty( $connection_map['internal'] ) ) {
			$connection_map['internal'] = array();
		} else {
			if ( 
				isset( $connection_map['internal'][ (int) $this->remote_site->blog_id ] ) 
				||
				isset( $connection_map['internal'][ $this->current_site_id ] ) 
			) {
				// this is the scenario,
				// we want avoid
				// 
				// if this is present, it can't be the first publsihing
				return;
			}
		}

		if ( empty( $connection_map['external'] ) ) {
			$connection_map['external'] = array();
		}

		$push_args = [];
		$push_args['post_status'] = $this->post_status;

		
/*
error_log(var_export([
	'$post_id' => $post_id,
	'get_current_blog_id()' => \get_current_blog_id(),
	'$this->current_site_id' => $this->current_site_id,
	'$this->remote_site' => $this->remote_site->blog_id,
	'$_dt_original_post_id' => $_dt_original_post_id,
	'$_dt_original_blog_id' => $_dt_original_blog_id,
	'$handle' => $this->post_status.'_'.$post->post_type,
	'$connection_map'=>$connection_map,
], true));
*/
		// prevent recursion,
		// when calling this function on {new_status}_{post_type} hook,
		// which is situated inside of wp_insert_post(), 
		// which is called by $connection->push()
		// 
		// $handle = current_filter(); // looks easier to read, but is much more error-prone -> DONT DO IT

		$handle = $this->post_status.'_'.$post->post_type;
		\remove_action( $handle, [ $this, __FUNCTION__ ], 10 );

			//
			$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $this->remote_site ); 
			// 
			$remote_post = $connection->push( 
				$post_id,
				$push_args
			);

		// THIS MAYBE PREVENTS CHAINING OF DISTRIBUTION
		// test if auto-distribution works from site A -> site B -> site C - in one go
		// 
		// prevent recursion,
		// when calling this function on {new_status}_{post_type} hook,
		// which is situated inside of wp_insert_post(), 
		// which is called by $connection->push()
		// 
		// ...
		// so, dont re-add this again ??
		// so easy ???
		// maybe
		\add_action( $handle, [ $this, __FUNCTION__ ], 10, 2 );



		/**
		 * Record the internal connection id's remote post id for this local post
		if ( \is_wp_error( $remote_post ) ) {
			\do_action( 'qm/error', $remote_post );
			return;
		}
		 */

		$connection_map['internal'][ (int) $this->remote_site->blog_id ] = array(
			'post_id' => (int) $remote_post['id'],
			'time'    => time(),
		);

		//
		\update_post_meta( $post_id, 'dt_connection_map', $connection_map );
	}

}
