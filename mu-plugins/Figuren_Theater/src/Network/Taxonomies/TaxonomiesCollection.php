<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\SiteParts as SiteParts;


// Static Proxy.
final class TaxonomiesCollection extends SiteParts\SitePartsCollectionAbstract
{


#	const PROXIED_CLASS = 'ProxiedTaxonomiesCollection';
#	protected static $Proxied_class = 'ProxiedTaxonomiesCollection';

	public static function get_collection() : SiteParts\SitePartsCollectionInterface {

		static $collection = null;

		if ( null === $collection ) {
			// You can have arbitrary logic in here to decide what
			// implementation to use.
			$collection = new ProxiedTaxonomiesCollection();
#			$collection = new self :: $Proxied_class;

		}

		return $collection;
	}
}


// Call it via API:
// \Figuren_Theater\API::get('TAX')->get|add|remove()
// 
// 
// Call this the 'normal' way:
// \Figuren_Theater\Network\Taxonomies\TaxonomiesCollection::add();
// \Figuren_Theater\Network\Taxonomies\TaxonomiesCollection::get();
// 
// 
// ... therefore it has to be registered to the API

\Figuren_Theater\API::add('TAX', __NAMESPACE__.'\\TaxonomiesCollection::get_collection' );
