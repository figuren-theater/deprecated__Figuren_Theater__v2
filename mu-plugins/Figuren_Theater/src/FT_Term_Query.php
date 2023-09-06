<?php
declare(strict_types=1);

namespace Figuren_Theater;


/**
 * Designing a class to manage WordPress terms
 *
 * @see https://developer.wordpress.org/reference/classes/wp_term_query/ 
 * @see https://wordpress.stackexchange.com/questions/375267/retrieve-taxonomies-from-arbitrary-site 
 *
 *
 * based on FT_Query and heavily inspiredby and copied from
 *
 * @author  Carl Alexander
 * @see     https://carlalexander.ca/designing-class-manage-wordpress-posts/ [<description>]
 */
/**
	Example of use:
------------------------------------------

	$ft_term_query = FT_Term_Query::init();

	// find_by_id example
	$term = $ft_term_query->find_by_id(1);

-------------------------------------------
 */
class FT_Term_Query
{
	/**
	 * WordPress query object.
	 *
	 * @var WP_Term_Query
	 */
	private $query;

	/**
	 * Constructor.
	 *
	 * @param WP_Term_Query $query
	 */
	public function __construct(\WP_Term_Query $query)
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
		return new self(new \WP_Term_Query());
	}



	/**
	 * Find terms using the given post ID.
	 *
	 * @param int $id
	 *
	 * @return WP_Term[]
	 */
	public function find_by_post(int $id) : array
	{
		return $this->find(array('object_ids' => [ $id ] ));
	}



	/**
	 * Find all term objects for the given query.
	 *
	 * @param array $query
	 *
	 * @return WP_Term[]
	 */
	private function find(array $query) : array
	{
		$query = array_merge(array(
			'cache_domain' => __NAMESPACE__,
			'update_term_meta_cache' => true,
		), $query);

		return $this->query->query($query);
	}


	public function find_by_tax(String $taxonomy)
	{
		return $this->find(array('taxonomy' => $taxonomy ));
	}


	public function slugs_by_tax(String $taxonomy)
	{
		return $this->find(array(
			'taxonomy' => $taxonomy,
			'fields'   => 'slug',
			'get'      => 'all',
		));
		
	}
}
