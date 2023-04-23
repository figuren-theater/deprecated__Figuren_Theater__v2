<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

use Figuren_Theater\SiteParts;

use WP_Taxonomy;


class TaxonomiesManager extends SiteParts\DataManager {

	/**
	 * Overwrite __construct of parentClass 'DataManager'
	 * to add TypeHinting onto the given Registerer.
	 * So we can be sure only RegistrationHandlers with
	 * abilities for Taxonomies are used to set up this major SitePartManager.
	 * 
	 * @param TaxonomyRegistration $registerer InterFace for registering a new WP_Taxonomy
	 */
	function __construct( TaxonomyRegistration $registerer, SiteParts\SitePartsCollectionInterface $collection ) {
		parent::__construct( $registerer, $collection );
	}


	// runs on 'init' 0
	public function prepare_data_type() : void {


		// Run on every element of our collection
		array_map(
			// make sure the element is a valid 'Taxonomy__CanInitEarly__Interface' 
			// so it has the method 'init_taxonomy()'
			function( $collection_el ) {
				// not part of the function(input) anymore,
				// because it was helpful, to also have some defaul 
				// 'WP_Taxonomy' objects inside the collection for modification
				// and THOOSE ARE WELL PREPARED, SHITTY CODING OVER HERE ....
				if ( ! $collection_el instanceof Taxonomy__CanInitEarly__Interface)
					return;

				// Setup taxonomy properties
				// do this LATE to make sure,
				// several conditionals and the users state
				// are available for its methods
				$collection_el->init_taxonomy();
			},
			// Rund on all elements,
			// but not its names (,the array keys)
			array_values( $this->collection->get() )
		);

	}


	// runs on init 10 on every collection element
	public function register( $collection_el ) : bool {

		if ( $collection_el instanceof WP_Taxonomy ) {
			$_tax_object = \register_taxonomy( $collection_el->name, $collection_el->object_type, (array) $collection_el );
		} else {
			$_tax_object = $this->registerer->register( $collection_el );
		}

		// jump out if something went wrong
		if ( ! $_tax_object instanceof WP_Taxonomy )
			return false;


		if ( ! empty( $_tax_object->object_type ) ) {
			foreach ( $_tax_object->object_type as $pt ) {
				\register_taxonomy_for_object_type( $_tax_object->name, $pt );
			}
		}

		return $this->collection->add( $_tax_object->name, $_tax_object );
	}
}

\add_action( 
	'Figuren_Theater\init', 
	function ( $ft_site ) : void {

		if ( ! is_a( $ft_site, 'Figuren_Theater\ProxiedSite' ))
			return;

		// 5.   Setup all Taxonomies as part of our Collection
		// 5.1. Create Collection 
		// It's important, to do that before ADDing taxonomies,
		// to properly instantiate our collection.
		$collection = TaxonomiesCollection::get_collection();

		// 5.2. Add all Taxonomies to the collection
		// Das ZIEL
		// call from everywhere
		// ( Using a Static Proxy: )
		//      
		// Example from https://www.alainschlesser.com/singletons-shared-instances/
		// $result = Services::get( 'Database' )->query( $query );
		//      
		// PSEUDOCODE
		// $result = API::get( 'SitePart' )->add|update|remove( ...$args );
		// Register Taxonomy for shadowing the 'ft_site' post_type on this site
		// \Figuren_Theater\API::get( 'TAX' )->add( Taxonomy__ft_site_shadow::NAME, new Taxonomy__ft_site_shadow() );
		$collection->add( Taxonomy__ft_site_shadow::NAME, new Taxonomy__ft_site_shadow() );
		// \Figuren_Theater\API::get( 'TAX' )->add( Taxonomy__ft_feature_shadow::NAME, new Taxonomy__ft_feature_shadow() );
		$collection->add( Taxonomy__ft_feature_shadow::NAME, new Taxonomy__ft_feature_shadow() );
		// \Figuren_Theater\API::get( 'TAX' )->add( Taxonomy__ft_level_shadow::NAME, new Taxonomy__ft_level_shadow() );
		$collection->add( Taxonomy__ft_level_shadow::NAME, new Taxonomy__ft_level_shadow() );
		// \Figuren_Theater\API::get( 'TAX' )->add( Taxonomy__ft_geolocation::NAME, new Taxonomy__ft_geolocation() );
		$collection->add( Taxonomy__ft_geolocation::NAME, new Taxonomy__ft_geolocation() );
		// \Figuren_Theater\API::get( 'TAX' )->add( Taxonomy__ft_production_shadow::NAME, new Taxonomy__ft_production_shadow() );
		$collection->add( Taxonomy__ft_production_shadow::NAME, new Taxonomy__ft_production_shadow() );
		// \Figuren_Theater\API::get( 'TAX' )->add( Taxonomy__ft_az_index::NAME, new Taxonomy__ft_az_index() );
		$collection->add( Taxonomy__ft_az_index::NAME, new Taxonomy__ft_az_index() );

		/**
		 * 5.3. Setup SitePart Manager for 'Taxonomies'
		 * with its personal RegistrationHandler and our
		 * prepared Collection
		 */
		$ft_site->set_TaxonomiesManager(
			new TaxonomiesManager(
				// with its RegistrationHandler.
				new ExtendedCPT_TaxonomyRegisterer(),
				// and its TaxonomiesCollection.
				$collection
			)
		);
	},
	60
);
