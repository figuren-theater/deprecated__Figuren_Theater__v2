<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;



class Feature__favicon extends Features\Feature__Abstract
{

	const SLUG = 'favicon';

	public function enable() : void
	{
		// moved into 
		// Figuren_Theater\Theming\Favicon_Fallback
	}

	public function enable__on_admin() : void {}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
