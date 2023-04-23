<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;

use Figuren_Theater\SiteParts;

// Static Proxy.
final class FeaturesCollection extends SiteParts\SitePartsCollectionAbstract
{

	public static function get_collection() : SiteParts\SitePartsCollectionInterface {

		static $collection = null;

		if ( null === $collection ) {
			// You can have arbitrary logic in here to decide what
			// implementation to use.
			$collection = new ProxiedFeaturesCollection();
		}

		return $collection;
	}
}

// Call it via API:
// \Figuren_Theater\API::get('FEAT')->get|add|remove()
// 
// 
// Call this the 'normal' way:
// \Figuren_Theater\Network\Features\FeaturesCollection::add();
// \Figuren_Theater\Network\Features\FeaturesCollection::get();
// 
// 
// ... therefore it has to be registered to the API
\Figuren_Theater\API::add('FEAT', __NAMESPACE__.'\\FeaturesCollection::get_collection' );
