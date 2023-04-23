<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\inc\EventManager as Inc;
use Shadow_Taxonomy\Core as Shadow_Taxonomy;

use WP_Post;
use WP_Term;

use Distributor\Utils;

/**
 * This is class is used to create object 2 object relations between posts 
 * using the existing logic from 
 * @see [URI | FQSEN] [<description>]
 * 
 */
class TAX_Shadow implements Inc\SubscriberInterface
{
	/**
	 * the (existing) taxonomy,
	 * where shadows of posts 
	 * will be created as terms.
	 * 
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * The post_type to create a shadow of
	 * @var string
	 */
	protected $post_type;

	/**
	 * Identifier of the $term_meta field,
	 * which stores the shadowed post-ID.
	 * 
	 * @var string
	 */
	protected $term_meta_key;


	/**
	 * Identifier of the $post_meta field,
	 * which stores the shadowing term-ID.
	 * 
	 * @var string
	 */
	protected $post_meta_key;



	function __construct( $taxonomy, $post_type )
	{
		$this->taxonomy  = $taxonomy;
		$this->post_type = $post_type;

		$this->term_meta_key = "shadow_{$taxonomy}_post_id";
		$this->post_meta_key = "shadow_{$taxonomy}_term_id";


		// $this->load_dependencies();
		$this->hook_into_term_link_filter();
	}

	// protected function load_dependencies() : void {}

	public static function init()
	{
		$static = new static('',''); // tricky ;) // maybe TODO
		return $static;
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
			// TRY
			// 'after_setup_theme' => 'hook_into_term_link_filter', // too early
			// 'after_setup_theme' => 'hook_into_term_link_filter', // must be init 6 - 10

			// Register shadow connection
			// 0 init taxonomies & post_types within our TaxonomiesCollection
			// 10 registering of default & third party tax & pt
			// 11 registering our data-types
			// 12 // make sure taxonomy and post_type are already registered
			'init' => ['create_relationship', 12],

			// Sync post_excerpt as term_description
			// 15 // wait for all (typical) data manipulation beeing done
			'wp_insert_post' => ['sync_excerpt_to_term_description', 15, 3],

			// https://10up.github.io/distributor/dt_excluded_meta.html
			'dt_excluded_meta' => 'dt_excluded_meta', 

		);
	}

	/**
	 * Filter: [dt_excluded_meta description]
	 *
	 * @uses https://10up.github.io/distributor/dt_excluded_meta.html 
	 * 
	 * @param  [type] $meta_keys [description]
	 * @return [type]            [description]
	 */
	public function dt_excluded_meta( $meta_keys ) : array
	{
		$meta_keys[] = $this->post_meta_key;
		return $meta_keys;
	}


	/**
	 * By default /ShadowTaxonomy only syncs slug and title
	 */
	public function sync_excerpt_to_term_description( $post_ID, $post, $update ) : void
	{
		if ( $this->post_type !== $post->post_type)
			return;

		if ($shadow_term_id = \get_post_meta( $post_ID, $this->post_meta_key, true )) {
			$shadow_term    = \get_term( (int) $shadow_term_id, $this->taxonomy );
			\wp_update_term( (int) $shadow_term_id, $this->taxonomy, array(
				'description' => $post->post_excerpt
			) );
		}
	}

	/**
	 * This is ugly,
	 * because it uses closures to add_actions on
	 * important callbacks like 
	 * - wp_insert_post
	 * - before_delete_post
	 * which is not nicely removable,
	 * if needed.
	 *
	 * We need this functionality added 
	 * on the fly during 'publish_ft_site' && 'switch[ed]_blog[s]' 
	 * and removed instantly afterwards.
	 * So closures are ugly.
	 * @return     void     ugly closure
	 */
	public function create_relationship() : void
	{
		// Use the Shadow Taxonomy Library API 
		// to create an association between tax and post_type
		Shadow_Taxonomy\create_relationship( $this->post_type, $this->taxonomy );

		// Register post- and term-meta to be used within gutenberg
		$this->register_meta();
	}


	protected function register_meta()
	{

		// register term_meta for REST 
		\register_term_meta( 
			$this->taxonomy,
			$this->term_meta_key,
			[
				'type' => 'integer',
				#'description' => 'a nice description',
				'single' => true,
				'show_in_rest' => true,
				#'show_in_rest' => array(
				#	'schema' => array(
				#		'type' => 'string',
				#		'format' => 'url',
				#		'context' => array( 'view', 'edit' ),
				#		'readonly' => true,
				#   )
				#)
			]
		);
		
		//
		\register_post_meta(
			$this->post_type,
			$this->post_meta_key,
			[
				'type' => 'integer',
				#'description' => 'a nice description',
				'single' => true,
				'show_in_rest' => true,
				#'show_in_rest' => array(
				#	'schema' => array(
				#		'type' => 'string',
				#		'format' => 'url',
				#		'context' => array( 'view', 'edit' ),
				#		'readonly' => true,
				#   )
				#)
			]
		);

	}



	/**
	 * Wrapper to be used,
	 * to make sure dependencies are loaded.
	 *
	 * Note: union types were introduced in PHP 8.0 !
	 * 
	 * @param  WP_Post $post     [description]
	 * @param  string  $taxonomy [description]
	 * @return WP_Term|false           [description]
	 */
	public function get_associated_term( WP_Post $post, string $taxonomy ) : WP_Term|false
	{
		return Shadow_Taxonomy\get_associated_term( $post, $taxonomy );
	}

	// @TODO
	public function get_associated_post(){}
	

	/**
	 * Function gets the associated shadow post of a given term object.
	 *
	 * @param object $term WP Term Object.
	 *
	 * @return bool | int return the post_id or false if no associated post is found.
	 */
	public function get_associated_post_id( WP_Term $term ) : int|false {
		return Shadow_Taxonomy\get_associated_post_id( $term );
	}


	public function hook_into_term_link_filter()
	{

		// get ft-tax-obj
		$_tax = \Figuren_Theater\API::get('TAX')->get( $this->taxonomy );

		// 
		if ( ! $_tax instanceOf Taxonomy__LinksShadowedPost__Interface )
			return;

		//
		\add_filter( 'term_link', [$this, 'term_link_to_post'], 10, 3);

	}



	/**
	 * Filters the term link.
	 *
	 * @since   WP 2.5.0
	 * @see     https://developer.wordpress.org/reference/hooks/term_link/
	 *
	 * @package project_name
	 * @version version
	 * @author  Carsten Bach
	 *
	 * @param   string       $termlink Term link URL.
	 * @param   WP_Term      $term     Term object.
	 * @param   string       $taxonomy Taxonomy slug.
	 * 
	 * @return  [type]                 [description]
	*/
	public function term_link_to_post( string $termlink, \WP_Term $term, string $taxonomy ) : string
	{

		// only public
		if ( \is_admin() )
			return $termlink;

		// only for our tax
		if ( $this->taxonomy !== $taxonomy )
			return $termlink;

		$_level = (int) Shadow_Taxonomy\get_associated_post_id( $term );

		if ( 0 < $_level )
		{
			return \get_permalink( $_level );
		}
		
		//
		return $termlink;
		
	}
}
