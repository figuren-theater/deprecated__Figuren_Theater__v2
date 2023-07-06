<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;


class Feature__schoener_teilen extends Features\Feature__Abstract
{

	const SLUG = 'schoener-teilen';

	public function enable() : void {
		\add_filter( 
			'figuren_theater.config', 
			function ( array $config ) : array {
				$config['modules']['seo']['sharing-image'] = true;
				return $config;
			}
		);
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
