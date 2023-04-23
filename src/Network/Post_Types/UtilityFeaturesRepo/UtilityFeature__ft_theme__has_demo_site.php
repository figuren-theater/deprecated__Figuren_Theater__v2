<?php
declare(strict_types=1);

namespace Figuren_Theater\UtilityFeaturesRepo;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Coresites\Post_Types;

/**
 * 
 */
class UtilityFeature__ft_theme__has_demo_site extends Features\UtilityFeature__Abstract {
	const SLUG = 'ft_theme-has-demo-site';

	function __construct() {
		parent::__construct(
			__('Has demo site.','figurentheater'),
			false,
			Post_Types\Post_Type__ft_theme::NAME
		);
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the /Figuren_Theater/src/Network/Post_Types/UtilityFeaturesRepo ;)
