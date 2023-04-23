<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;

class Feature__in_foermlicher_sprache extends Features\Feature__Abstract {

	const SLUG = 'in-foermlicher-sprache';


	public function enable() : void {
		// The 'preferred-languages' Plugin is loaded 
		// by Figuren_Theater\Onboarding\Preferred_Languages
		// everywhere, but re-acts on this feature.
	}

	// also load here, 
	// because this needs to be activated on every site
	// to provide a i18n-fallback-chain
	public function disable() : void {
		// The 'preferred-languages' Plugin is loaded 
		// by Figuren_Theater\Onboarding\Preferred_Languages
		// everywhere, but re-acts on this feature.
	}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
