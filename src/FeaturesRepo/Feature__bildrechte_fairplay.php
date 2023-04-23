<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;

use function add_filter;


class Feature__bildrechte_fairplay extends Features\Feature__Abstract
{

	const SLUG = 'bildrechte-fairplay';

	public function enable() : void {

		add_filter( 
			'Figuren_Theater.config', 
			function ( array $config ) : array {
				$config['modules']['site_editing']['image-source-control-isc'] = true;
				return $config;
			}
		);
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
