<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;
use Figuren_Theater\Network\Features as Features;



class Feature__ohne_schlagworte extends Features\Feature__Abstract
{

	const SLUG = 'ohne-schlagworte';

	public function enable() : void {
		// our TaxonomiesManager 
		// can pickup the empty WP_Taxonomy object and 
		// disable the TAX on the normal init
		\Figuren_Theater\API::get('TAX')->add( 'post_tag', new \WP_Taxonomy('post_tag', [], []) ); // WORKING  !!!!
	}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
