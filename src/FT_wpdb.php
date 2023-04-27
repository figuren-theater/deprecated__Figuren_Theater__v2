<?php
declare(strict_types=1);

namespace Figuren_Theater;
use Figuren_Theater\Network\Post_Types as Post_Types;



/**
 * Working with the $wpdb global
 */
class FT_wpdb
{

	function __construct()
	{
		// global $wpdb;
		// $this->wpdb = $wpdb;
	}

	public static function init()
	{
		static $instance;

		if ( NULL === $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * This is vodoo !
	 * 
	 * We grab all feature-related terms of our website
	 * by querying the DB directly.
	 * 
	 * Because acting on taxonomies and (custom) post_types
	 * like we would use here with a normal get_the_terms() call,
	 * won't work. It's too early for them.
	 * 
	 * Taxonomies and Posttypes are set up on 'init', but our 'Features' 
	 * should probably manage Plugins, Options, Themes 
	 * and also Taxonomies and Posttypes itself.
	 * 
	 * So we have to make sure, we know them as early as possible.
	 * That's why we go for $wpdb ;)
	 * 
	 *
	 * @todo   Move this into separate class
	 * 
	 * @param      String       $taxonomy [description]
	 * @param      String       $taxonomy [description]
	 * @param      Int          $post_id  [description]
	 * 
	 * @return     Array        ['term-slug','another-term-slug','one-more-term-slug'...]
	 */
	public function get_terms_by_tax_and_post_id( array $taxonomies, Int $post_id ) : array
	{
		global $wpdb;

		// WORKING
		// prepare the SQL
		// using $wpdb global to have everything in place
		// WHERE r.object_id IN('%s') AND tt.taxonomy IN('%s')
			
		$where_or__from_array = $this->where_or__from_array( 'tt.taxonomy', $taxonomies );
		$query = $wpdb->prepare(
			"SELECT t.* from $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
			WHERE r.object_id IN('%s') $where_or__from_array
			GROUP BY t.term_id",
			$post_id
			
		);

		// do the query and
		// get back some terms
		$results = $wpdb->get_results( $query );

		// clean up result
		// we could also use the term->names
		// but for now, this is enough
		if ( ! empty( $results ) ) {
			// returns [ term-ID => term-slug], what we had at first (01/2021)
			// $results = wp_list_pluck( $results, 'slug', 'term_id' );
			
			// reduced to what is really needed ['term-slug','another-term-slug','one-more-term-slug'...]
			$results = \wp_list_pluck( $results, 'slug' );
		}

		// could be an empty or 'full' array
		return $results;
	}

	
	/**
	 * This is vodoo !
	 * 
	 * We grab all feature-related terms of our website
	 * by querying the DB directly.
	 * 
	 * Because acting on taxonomies and (custom) post_types
	 * like we would use here with a normal get_the_terms() call,
	 * won't work. It's too early for them.
	 * 
	 * Taxonomies and Posttypes are set up on 'init', but our 'Features' 
	 * should probably manage Plugins, Options, Themes 
	 * and also Taxonomies and Posttypes itself.
	 * 
	 * So we have to make sure, we know them as early as possible.
	 * That's why we go for $wpdb ;)
	 * 
	 *
	 * @todo   Move this into separate class
	 * 
	 * @param      Array        $taxonomy list of taxonomies, to search terms for
	 * @param      Int          $post_id  the ID of the post, which terms to pick from
	 * 
	 * @return     Array        ['term-slug','another-term-slug','one-more-term-slug'...]
	 */
	public function get_term_slugs_by_tax_and_post_id( array $taxonomies, Int $post_id ) : array
	{
		global $wpdb;
		// WORKING
		// prepare the SQL
		// using $wpdb global to have everything in place
			// WHERE r.object_id IN('%s') AND tt.taxonomy IN('%s')
	
		$where_or__from_array = $this->where_or__from_array( 'tt.taxonomy', $taxonomies );
		$query = $wpdb->prepare(
			"SELECT t.slug from $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
			WHERE r.object_id IN('%s') $where_or__from_array
			GROUP BY t.term_id",
			$post_id
			
		);

		// do the query and
		// get back some terms
		// 
		// you could think using '$wpdb->get_col()' would better fit our query
		// yes, but ...
		// $results = $wpdb->get_col( $query, 0 );
		
		// its slower , so go for '$wpdb->get_results' & 'array_column'
		$results = $wpdb->get_results( $query, ARRAY_N );

		// clean up result
		// we could also use the term->names
		// but for now, this is enough
		if ( ! empty( $results ) ) {
			// returns [ term-ID => term-slug], what we had at first (01/2021)
			// $results = wp_list_pluck( $results, 'slug', 'term_id' );
			
			// reduced to what is really needed ['term-slug','another-term-slug','one-more-term-slug'...]
			// $results = \wp_list_pluck( $results, 'slug' );
			$results = \array_column( $results, 0 );
		}

		// could be an empty or 'full' array
		return $results;
	}

	protected function where_or__from_array( String $fieldname, Array $fields ) : string
	{
		$where_or_statement = array_map(
			function( $field ) use ( $fieldname )
			{
				return "{$fieldname} IN('{$field}')";
			},
			$fields
		);

		if (1 === count($where_or_statement)) {
			$return = $where_or_statement[0];
		} else {
			$return = implode(' OR ', $where_or_statement);
			$return = "( {$return} )";
		}


		return " AND " . $return;
	}

	public function get_ft_site_post()
	{
		global $wpdb;

		$query = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s ORDER BY id ASC", Post_Types\Post_Type__ft_site::NAME );

		$return = $wpdb->get_row( $query );

		return $return;
	}

	public function get_ft_site_post_id() : int
	{
		global $wpdb;

		// $query = $wpdb->prepare( "SELECT id FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s ORDER BY id ASC", Post_Types\Post_Type__ft_site::NAME );
		$query = $wpdb->prepare( "SELECT id FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s ORDER BY id ASC LIMIT 1", Post_Types\Post_Type__ft_site::NAME );

		$return = intval( $wpdb->get_var( $query ) );

		return $return;
	}

}































/*


debug_ft_FT_wpdb();


function debug_ft_FT_wpdb_2(){




// run our well prepared WP_Query
  $start2 = microtime(true);
  // function code here
$_wpdb = \Figuren_Theater\FT_wpdb::init();
$v2 = $_wpdb->get_term_slugs_by_tax_and_post_id(
	[
		\Figuren_Theater\Network\Features\UtilityFeaturesManager::TAX,
		\Figuren_Theater\Network\Taxonomies\Taxonomy__ft_feature_shadow::NAME
	],
	\Figuren_Theater\FT::site()->get_site_post_id()
);
  $time_taken2 = microtime(true) - $start2;

  return $time_taken2;

}



function debug_ft_FT_wpdb(){




// run our well prepared WP_Query
  $start1 = microtime(true);
  // function code here
$_wpdb = \Figuren_Theater\FT_wpdb::init();
$v1 = $_wpdb->get_terms_by_tax_and_post_id(
	[
		\Figuren_Theater\Network\Features\UtilityFeaturesManager::TAX,
		\Figuren_Theater\Network\Taxonomies\Taxonomy__ft_feature_shadow::NAME
	],
	\Figuren_Theater\FT::site()->get_site_post_id()
);
  $time_taken1 = microtime(true) - $start1;


// run our well prepared WP_Query
  $start2 = microtime(true);
  // function code here
$_wpdb = \Figuren_Theater\FT_wpdb::init();
$v2 = $_wpdb->get_term_slugs_by_tax_and_post_id(
	[
		\Figuren_Theater\Network\Features\UtilityFeaturesManager::TAX,
		\Figuren_Theater\Network\Taxonomies\Taxonomy__ft_feature_shadow::NAME
	],
	\Figuren_Theater\FT::site()->get_site_post_id()
);
  $time_taken2 = microtime(true) - $start2;

#// run our well prepared WP_Query
#  $start3 = microtime(true);
#  // function code here
#  $ft_query->find_first_ft_site();
#  $time_taken3 = microtime(true) - $start3;


  $time_taken3 = debug_ft_FT_wpdb_2();
#
#// run our well prepared WP_Query
#  $start4 = microtime(true);
#  // function code here
#  $v4 = $ft_query->use_cache( 'find_first_ft_site', 'Figuren_Theater', [$ft_query,'find_first_ft_site'] );
#  $time_taken4 = microtime(true) - $start4;

// Init our WP_Query wrapper
$ft_query = FT_Query::init();
$time_taken4 = $ft_query->use_cache( 'debug_ft_FT_wpdb_2', 'Figuren_Theater', __NAMESPACE__.'\\debug_ft_FT_wpdb_2' );
$ft_query = FT_Query::init();
$time_taken5 = $ft_query->use_cache( 'debug_ft_FT_wpdb_2', 'Figuren_Theater', __NAMESPACE__.'\\debug_ft_FT_wpdb_2' );


	wp_die(
		'<pre>'.
		var_export(
			array(
				// do not rely on any domain-logic 
				// to prevent errors with custom domain names
#				parse_url( $_SERVER['HTTP_HOST'] ),
				// go for 
				// typicall results looked like this 
				// 						// time spent on functions (
				$time_taken1,			//  0 => 0.0003788471221923828,
				$time_taken2,			//  1 => 0.00019407272338867188,
				$time_taken3,			//  2 => 0.000885009765625,
				$time_taken4,			//  
				$time_taken5,			//  

				$v1,
				$v2,
#				$v4,
#				FT::site(),
			),
			true
		).
		'</pre>'
	);
}
*/
