<?php
declare(strict_types=1);

namespace Figuren_Theater;
use Figuren_Theater\Network\Post_Types;



/**
 * Designing a class to manage WordPress posts
 *
 * @author  Carl Alexander
 * @see     https://carlalexander.ca/designing-class-manage-wordpress-posts/ [<description>]
 */
/**
	Example of use:
------------------------------------------

	$ft_query = FT_Query::init();

	// find_by_id example
	$post = $ft_query->find_by_id(1);

	// find_by_author example
	$current_user = wp_get_current_user();
	$posts = array();
	if ($current_user instanceof WP_User) {
		$posts = $ft_query->find_by_author($current_user);
	}
-------------------------------------------
 */
class FT_Query
{
	/**
	 * WordPress query object.
	 *
	 * @var WP_Query
	 */
	private $query;

	/**
	 * Constructor.
	 *
	 * @param WP_Query $query
	 */
	public function __construct(\WP_Query $query)
	{
		$this->query = $query;
	}


	/**
	 * Initialize the repository.
	 *
	 * @uses PHP 5.3
	 *
	 * @return self
	 */
	public static function init()
	{
		return new self(new \WP_Query());
	}


	/**
	 * Find posts written by the given author.
	 *
	 * @param WP_User $author
	 * @param int     $limit
	 *
	 * @return WP_Post[]
	 */
	public function find_by_author(\WP_User $author, $limit = 10)
	{
		return $this->find(array(
			'author' => $author->ID,
			'posts_per_page' => $limit,
		));
	}


	/**
	 * Find a post using the given post ID.
	 *
	 * @param int $id
	 *
	 * @return WP_Post|null
	 */
	public function find_by_id( int $id )
	{
		return $this->find_one(array('p' => $id));
	}


	/**
	 * Find 'ft_site'-posts on the main portal figuren.theater
	 * by "Distributor" created post_meta
	 * using its original post_id and the original blog_id as meta search params
	 *
	 * @param int     $original_post_id   from the original site
	 * @param int     $original_blog_id   from the original site
	 *
	 * @return WP_Post[]
	 */
	public function find_by_dt_meta( int $original_post_id, int $original_blog_id )
	{
		return $this->find_one(array(
			'post_type' => Post_Types\Post_Type__ft_site::NAME,
			'meta_query' => array(
				'relation' => 'AND', // both of below conditions must match
				array(
					'key' => 'dt_original_post_id',
					'value' => $original_post_id
				),
				array(
					'key' => 'dt_original_blog_id',
					'value' => $original_blog_id
				)
			),
		));
	}


	/**
	 * Find a 'ft_site' post using the given post ID.
	 *
	 * @param int $id
	 *
	 * @return WP_Post|null
	public function find_ft_site_by_id($id)
	{
		return $this->find_one(array(
			'p' => $id,
			'post_status' => ['publish','private'],
			'post_type' => Post_Types\Post_Type__ft_site::NAME
		));
	}
	 */


	/**
	 * Use this method to get the WP_Post obj
	 * of our current 'ft_site'
	 * without knowing its ID.
	 *
	 * This is only possible by the design
	 * of the application.
	 * 
	 * A post of this post_type is created during blog_creation.
	 * So we can be sure, 
	 * (1.) that there will be a post with that post_type and 
	 * (2.) that it will be the right one 
	 *      because results are sorted by (creation) ID, 
	 *      in case there are multiple ones, 
	 *      like on https://figuren.theater 
	 *      or https://mein.figuren.theater
	 *      
	 * @return [type] [description]
	 */
	public function find_first_ft_site()
	{
		return $this->find_one(array(
			'order' => 'ASC', // returns lowest first
			'orderby' => 'ID', // 
			'post_status' => ['publish','private'],
			'post_type' => Post_Types\Post_Type__ft_site::NAME
		));
	}


	/**
	 * [use_cache description]
	 *
	 * @see    https://10up.github.io/Engineering-Best-Practices/php/#the-object-cache
	 * 
	 * @param  string   $key           [description]
	 * @param  string   $group         [description]
	 * @param  Callable $callback      [description]
	 * @param  boolean  $force_refresh [description]
	 * 
	 * @return [type]                  [description]
	 */
	public function use_cache( string $key, string $group, callable $callback, bool $force_refresh = false  )
	{
		// Check for the cache key in the named group
		$cached_data = \wp_cache_get( $key, $group );

		// 
		if ( true === $force_refresh || false === $cached_data ) {
			// grab the data, what to cache and return
			$cached_data = $callback();

			// if ( ! is_wp_error( $cached_data ) && $cached_data->have_posts() ) {
			if ( ! \is_wp_error( $cached_data ) ) {
				// Cache the whole WP_Query object in the cache and store it for 5 minutes (300 secs).
				\wp_cache_set( $key, $cached_data, $group );
			}
		}

		return $cached_data;

	}


	public function find_many_by_type( string $post_type, string $post_status = 'any', $query = null ) : array {
		$default = array(
			'post_type' => $post_type,
			// 'fields' => 'post_name', // Specifying any other value than (ids|id=>parent) will return all fields (default) - array of post objects.
			'posts_per_page' => 100,
			'post_status' => $post_status,
		);

		if( is_array( $query ) && !empty( $query ) )
			$default = array_merge( $default, $query );

		return $this->find( $default );
	}

	/**
	 * Find all post objects for the given query.
	 *
	 * @param array $query
	 *
	 * @return WP_Post[]
	 */
	private function find( array $query ) : array {
		$query = array_merge(array(
			'no_found_rows' => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		), $query);

		return $this->query->query($query);
	}


	/**
	 * Find a single post object for the given query. Returns null
	 * if it doesn't find one.
	 *
	 * @param array $query
	 *
	 * @return WP_Post|null
	 */
	public function find_one(array $query)
	{
		$query = array_merge($query, array(
			'posts_per_page' => 1,
		));

		$posts = $this->find($query);

		return !empty($posts[0]) ? $posts[0] : null;
	}


	/**
	 * Insert or update a post into the repository. 
	 * 
	 * No need to call \wp_update_post(), 
	 * just pass an 'ID' with get_post_data().
	 * 
	 * Returns the post ID or a WP_Error.
	 *
	 * @uses wp_insert_post( $postarr, $wp_error ); 
	 * @see https://wp-kama.com/function/wp_insert_post 
	 * 
	 * @param Post_Type__CanCreatePosts__Interface $post
	 * @param bool                                 $wp_error    wether to return 0 or a WP_Error obj on failure
	 *
	 * @return int|WP_Error
	 */
	public function save( Post_Types\Post_Type__CanCreatePosts__Interface $post, $wp_error = false )
	{
		return \wp_insert_post( \wp_slash( $post->get_post_data() ), $wp_error);
	}
}
