<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;


class Feature__pwa extends Features\Feature__Abstract
{

	const SLUG = 'pwa';

	public function enable() : void {

		add_filter( 
			'figuren_theater.config', 
			function ( array $config ) : array {
				$config['modules']['performance']['pwa'] = true;
				return $config;
			}
		);


		#	if( ! \is_user_logged_in() )
		#	{
		#		\Figuren_Theater\API::get('Plugins')->add_to( 
		#			new PluginsRepo\share_target,
		#			'not_allowed'
		#		);
		#	}
	}

	public function disable() : void {}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
