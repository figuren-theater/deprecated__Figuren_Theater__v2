<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;
use Figuren_Theater\SiteParts as SiteParts;


// Static Proxy.
final class Post_TypesCollection extends SiteParts\SitePartsCollectionAbstract
{

	public static function get_collection() : SiteParts\SitePartsCollectionInterface {

		static $collection = null;

		if ( null === $collection ) {
			// You can have arbitrary logic in here to decide what
			// implementation to use.
			$collection = new ProxiedPost_TypesCollection();
		}

		return $collection;
	}
}

// Call it via API:
// \Figuren_Theater\API::get('PT')->get|add|remove()
// 
// 
// Call this the 'normal' way:
// \Figuren_Theater\Network\Post_Types\Post_TypesCollection::add();
// \Figuren_Theater\Network\Post_Types\Post_TypesCollection::get();
// 
// 
// ... therefore it has to be registered to the API
\Figuren_Theater\API::add('PT', __NAMESPACE__.'\\Post_TypesCollection::get_collection' );

