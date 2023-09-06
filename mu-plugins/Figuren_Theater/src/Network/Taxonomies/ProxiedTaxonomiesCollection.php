<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\SiteParts as SiteParts;


// Interface, so that we can deal with multiple implementations and properly
// mock for testing.
// we already have 
// this interface defined :)
// so no need to define thoose here 
/**
interface SitePartCollection {

	public function get( String $name = '' );
}
*/

// The implementation we're currently using.
final class ProxiedTaxonomiesCollection extends SiteParts\ProxiedSitePartsCollectionAbstract { // (inherited) // implements SiteParts\SitePartsCollection 

	// we already have 
	// add(), get() and remove() 
	// from the parent class 
	// 'SiteParts\ProxiedSitePartsCollectionAbstract'
	// so no need to define thoose here 
	/**
	public function get( String $name = '' ) {
		// Execute get and return results.
	}
	*/

	/**
	 * Get all or one SitePart from collection
	 * 
	 * @param  string $name [description]
	 * 
	 * @return Object|Array of Objects|anything ...
	 */
	public function get( String $name = '' )
	{
		if ( empty( $name ) ) {
			return $this->elements;
		}

		if ( $this->has( $name ) ) {
			return $this->elements[ $name ];
		} else {
			return \get_taxonomy( $name );
		}
	}

	protected function validate( $input ) : bool
	{
		return ( $input instanceof Taxonomy__CanInitEarly__Interface || $input instanceof \WP_Taxonomy );
	}
}
