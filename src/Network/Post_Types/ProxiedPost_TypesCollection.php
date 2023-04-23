<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;
use Figuren_Theater\SiteParts as SiteParts;


// Interface, so that we can deal with multiple implementations and properly
// mock for testing.
// we already have 
// this interface defined :)
// so no need to define thoose here 
/**
interface SitePartsCollection {

	public function get( String $name = '' );
}
*/

// The implementation we're currently using.
final class ProxiedPost_TypesCollection extends SiteParts\ProxiedSitePartsCollectionAbstract { // (inherited) // implements SiteParts\SitePartsCollection 

	// we already have 
	// add(), get() and remove() 
	// from the parent class 
	// 'SiteParts\ProxiedSitePartsCollectionAbstract'
	// so no need to define thoose here 

	/**
	 * Get all or one SitePart from collection
	 * 
	 * @param  string $name [description]
	 * 
	 * @return WP_Post_Type|null WP_Post_Type object if it exists, null otherwise.
	 */
	public function get( String $name = '' )
	{
		if ( empty( $name ) ) {
			return $this->elements;
		}

		if ( $this->has( $name ) ) {
			return $this->elements[ $name ];
		} else {
			return \get_post_type_object( $name );
		}
	}

	protected function validate( $input ) : bool
	{
		return ( $input instanceof Post_Type__CanInitEarly__Interface || $input instanceof \WP_Post_Type );
	}
}

