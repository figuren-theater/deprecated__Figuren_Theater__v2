<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;


class Feature__kommentare_ohne_spam extends Features\Feature__Abstract
{

	const SLUG = 'kommentare-ohne-spam';

	public function enable() : void {

		\add_filter( 
			'Figuren_Theater.config', 
			function ( array $config ) : array {
				$config['modules']['interactive']['comments'] = true;
				return $config;
			}
		);
	}

	/**
	 * Do we need this?
	 * 
	 *
	 * @since   2023.02.17
	 *
	 * 
	public function disable() : void {

		\add_filter( 
			'Figuren_Theater.config', 
			function ( array $config ) : array {
				$config['modules']['interactive']['comments'] = false;
				return $config;
			}
		);
	}
	 */
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
