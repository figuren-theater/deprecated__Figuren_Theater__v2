<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\SiteParts as SiteParts;


// Static Proxy.
final class Admin_UICollection extends SiteParts\SitePartsCollectionAbstract
{
	public static function get_collection() : SiteParts\SitePartsCollectionInterface {

		static $collection = null;

		if ( null === $collection ) {
			// You can have arbitrary logic in here to decide what
			// implementation to use.
			$collection = new ProxiedAdmin_UICollection();
		}
		return $collection;
	}
}


// Call it via API:
// \Figuren_Theater\API::get('Admin_UI')->get|add|remove()
// 
// 
// Call this the 'normal' way:
// \Figuren_Theater\Network\Admin_UI\Admin_UICollection::add();
// \Figuren_Theater\Network\Admin_UI\Admin_UICollection::get();
// 
// 
// ... therefore it has to be registered to the API

\Figuren_Theater\API::add('Admin_UI', __NAMESPACE__.'\\Admin_UICollection::get_collection' );
