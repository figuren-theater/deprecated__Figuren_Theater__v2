<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;
use Figuren_Theater\SiteParts as SiteParts;



class Post_TypesManager extends SiteParts\DataManager
{

	/**
	 * Overwrite __construct of parentClass 'DataManager'
	 * to add TypeHinting onto the given Registerer.
	 * So we can be sure only RegistrationHandlers with
	 * abilities for Post_Types are used to set up this major SitePartManager.
	 * 
	 * @param Post_TypeRegistration      $registerer      InterFace for registering a new \WP_Post_Type
	 */
	function __construct( Post_TypeRegistration $registerer, SiteParts\SitePartsCollectionInterface $collection )
	{
		parent::__construct( $registerer, $collection );
	}


	// runs on 'init' 0
	public function prepare_data_type() : void
	{


		// Run on every element of our collection
		array_map(
			// make sure the element is a valid 'Post_Type__CanInitEarly__Interface' 
			// so it has the method 'init_post_type()'
			function( $collection_el )
			{
				// not part of the function(input) anymore,
				// because it was helpful, to also have some defaul 
				// '\WP_Post_Type' objects inside the collection for modification
				// and THOOSE ARE WELL PREPARED, SHITTY CODING OVER HERE ....
				if (! $collection_el instanceof Post_Type__CanInitEarly__Interface)
					return;

				// Setup post_type properties
				// do this LATE to make sure,
				// several conditionals and the users state
				// are available for its methods
				$collection_el->init_post_type();
			},
			// Rund on all elements,
			// but not its names (,the array keys)
			array_values( $this->collection->get() )
		);

	}


	// runs on init 11 on every collection element
	public function register( $collection_el ) : bool
	{

		if ( $collection_el instanceof \WP_Post_Type)
		{
			$_pt_object = \register_post_type( $collection_el->name, (Array) $collection_el );
		}
		else
		{
			$_pt_object = $this->registerer->register( $collection_el );
		}

		// jump out if something went wrong
		if ( ! $_pt_object instanceof \WP_Post_Type )
			return false;

		// make sure this post_type is queried properly for all of its taxonomies
		// \add_action('pre_get_posts', ['include_cpts_in_builtin_queries'], 99);
		if ( $_pt_object->public )
			\add_action('pre_get_posts', function( $query ) use ( $_pt_object ) {
				$this->include_cpts_in_builtin_queries( $query, $_pt_object );
			}, 99);


		//everything ok, keep it
		return $this->collection->add( $_pt_object->name, $_pt_object );
	}



	/**
	 * MAYBE a chance for optimization with the help of this loooong comment thread at 
	 * @see https://css-tricks.com/snippets/wordpress/make-archives-php-include-custom-post-types/
	 * 
	 * @param  \WP_Query     $query     [description]
	 * @param  \WP_Post_Type $post_type [description]
	 */
	protected function include_cpts_in_builtin_queries( \WP_Query $query, \WP_Post_Type $post_type )
	{

		if (\is_admin())
			return; # early

		if (!$query->is_main_query())
			return; # early

		if (!\is_archive())
			return; # early

		if (\is_post_type_archive())
			return; # early

		if (\is_author())
			return; # early


#		if(!is_admin() && !is_post_type_archive() && is_archive() && $query->is_main_query()) :              // Ensure you only alter your desired query




#			\do_action( 'qm/warning', 'pre_get_posts with "{pt}"', [
#				'pt' => $post_type->name,
#			] );
#			\do_action( 'qm/warning', $post_type );
#
#			\do_action( 'qm/warning', 'taxonomies of "{pt}": {tax}', [
#				'pt' => $post_type->name,
#				'tax' => join(',',$post_type->taxonomies),
#			] );


#			\do_action( 'qm/debug', (
#					$query->is_tax()
#					||
#					$query->is_tag()
#					||
#					$query->is_category()
#				) );
#			\do_action( 'qm/debug', $post_type->taxonomies );
#			// \do_action( 'qm/debug', array_values( $post_type->taxonomies ) );
#			\do_action( 'qm/debug', $query->get_queried_object()->taxonomy );
#			\do_action( 'qm/warning', isset( $post_type->taxonomies[ $query->get_queried_object()->taxonomy ] ) );

// $_pt_taxs = array_values( $post_type->taxonomies );
$_pt_taxs = $post_type->taxonomies;

$_queried_object = $query->get_queried_object();
		if ( ! $_queried_object )
			return; # early

$_queried_tax = $_queried_object->taxonomy;
#			\do_action( 'qm/warning', isset( $_pt_taxs[ $_queried_tax ] ) );
#			\do_action( 'qm/warning', in_array($_queried_tax, $_pt_taxs) );


#die(var_dump($post_type));
#die(var_dump($post_type->taxonomies));
#die(var_dump($query->get_queried_object()));
// die(var_dump($query->get_queried_object()->taxonomy));
			if (
				(
					$query->is_tax()
					||
					$query->is_tag()
					||
					$query->is_category()
				)
				&&
				// !isset( $post_type->taxonomies[ $query->get_queried_object()->taxonomy ] )
				!in_array($_queried_tax, $_pt_taxs)
			)
				return; # early


			$post_types = $query->get('post_type');             // Get the currnet post types in the query
			if(!is_array($post_types) && !empty($post_types))   // Check that the current posts types are stored as an array
				$post_types = explode(',', $post_types);

			if(empty($post_types))                              // If there are no post types defined, be sure to include posts so that they are not ignored
				$post_types = array('post');

			$allow = (
				   $query->is_tag() 
				|| $query->is_category() 
				// || $query->is_tax(Taxonomies\Taxonomy__ft_geolocation::NAME) 
				|| $query->is_tax() 
				|| $query->is_author() 
				// || $query->is_date() 
			) ? true : false;

			// $categories = (array) $query->get('category_name');
			// if ( in_array('jobs', $categories) || $allow )
			if ( $allow )
				// $post_types[] = 'ft_job';                         // Add your custom post type
				$post_types[] = $post_type->name;                         // Add your custom post type

			$post_types = array_map('trim', $post_types);       // Trim every element, just in case
			$post_types = array_filter($post_types);            // Remove any empty elements, just in case

			$query->set('post_type', $post_types);              // Add the updated list of post types to your query

#		endif; 
	}

}



\add_action( 
	'Figuren_Theater\init', 
	function ( $ft_site ) : void {


// global $wp_actions;

// wp_die( '<pre>'.var_export(  [
	
// 	did_action( 'Figuren_Theater\loaded' ),
// 	$wp_actions,
// 	// \Figuren_Theater\FT::site(),
// 	__FILE__,

// ] , true ) .'</pre>');



		if ( ! is_a( $ft_site, 'Figuren_Theater\ProxiedSite' ))
			return;

		// 6. Setup all Post_Types as part of our Collection
		// 6.1. Create Collection 
		// It's important, to do that before ADDing post_types,
		// to properly instantiate our collection.
		$Post_TypesCollection = Post_TypesCollection::get_collection();

		// 6.2. Add all Post_Types to the collection
		// Das ZIEL
		// call from everywhere
		// ( Using a Static Proxy: )
		// 
		// Example from https://www.alainschlesser.com/singletons-shared-instances/
		// $result = Services::get( 'Database' )->query( $query );
		// 
		// PSEUDOCODE
		// $result = API::get( 'SitePart' )->add|update|remove( ...$args );
		\Figuren_Theater\API::get('PT')->add( Post_Type__ft_site::NAME, Post_Type__ft_site::get_instance() );

		\Figuren_Theater\API::get('PT')->add( Post_Type__ft_production::NAME, Post_Type__ft_production::get_instance() );


		// 6.3. Setup SitePart Manager for 'Post_Types'
		// with its personal RegistrationHandler and our 
		// prepared Collection
		$ft_site->set_Post_TypesManager( new Post_TypesManager( 
			// with its RegistrationHandler
			new ExtendedCPT_Post_TypeRegisterer,
			// and its Post_TypesCollection
			$Post_TypesCollection
		) );


	},
	70
);

