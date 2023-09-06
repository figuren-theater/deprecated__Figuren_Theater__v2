<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;

use Figuren_Theater\SiteParts;


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
final class ProxiedUtilityFeaturesCollection extends SiteParts\ProxiedSitePartsCollectionAbstract { // (inherited) // implements SiteParts\SitePartCollection 

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

	protected function validate( $input ) : bool
	{
		return ( $input instanceof UtilityFeature__Interface );
	}
}
