<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

/**
 * Set default taxonomy terms for (custom) post types
 * 
 * @see https://stackoverflow.com/questions/19706742/how-to-set-default-taxonomy-category-for-custom-post-types-in-wp
 */

class TaxonomiesAutoTerms {

	protected $taxonomy;

	function __construct( string $taxonomy )
	{
		$this->taxonomy = $taxonomy;
	}
	/**
	 * Add an default taxonomy term for any CPT on save
	 * 
	 * @access public
	 * @wp-hook save_post_{$post->post_type}
	 * @param integer $post_id
	 * @param object $post
	 */
	public function set_default_taxonomy_terms( int $post_id, \WP_Post $post )
	{
		if ( 'publish' === $post->post_status ) {

			$selected_taxonomy_terms = \wp_get_post_terms( $post_id, $this->taxonomy );

			if ( ! empty($selected_taxonomy_terms) )
				return;


			$default_taxonomy_terms = \get_option("default_{$this->taxonomy}_terms");

			// basic validation
			if ( 
					! is_string($default_taxonomy_terms)
				 && ! is_int($default_taxonomy_terms)
				 && ! is_array($default_taxonomy_terms)
				)
				return;

			// cast as Integer
			if ( is_string($default_taxonomy_terms) )
				$default_taxonomy_terms = (int) $default_taxonomy_terms;


			/**
			 * Create Term and Taxonomy Relationships.
			 *
			 * Relates an object (post, link etc) to a term and taxonomy type. Creates the
			 * term and taxonomy relationship if it doesn't already exist. Creates a term if
			 * it doesn't exist (using the slug).
			 *
			 * A relationship means that the term is grouped in or belongs to the taxonomy.
			 * A term has no meaning until it is given context by defining which taxonomy it
			 * exists under.
			 *
			 * @since 2.3.0
			 *
			 * @global wpdb $wpdb WordPress database abstraction object.
			 *
			 * @param int              $object_id The object to relate to.
			 * @param string|int|array $terms     A single term slug, single term ID, or array of either term slugs or IDs.
			 *                                    Will replace all existing related terms in this taxonomy. Passing an
			 *                                    empty value will remove all related terms.
			 * @param string           $taxonomy  The context in which to relate the term to the object.
			 * @param bool             $append    Optional. If false will delete difference of terms. Default false.
			 * @return array|WP_Error Term taxonomy IDs of the affected terms or WP_Error on failure.
			 */
			\wp_set_object_terms( $post_id, $default_taxonomy_terms, $this->taxonomy );
		}
	}


}
