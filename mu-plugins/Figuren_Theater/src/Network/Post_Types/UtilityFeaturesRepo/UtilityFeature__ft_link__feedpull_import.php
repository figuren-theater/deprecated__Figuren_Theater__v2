<?php
declare(strict_types=1);

namespace Figuren_Theater\UtilityFeaturesRepo;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;

/**
 * 
 */
class UtilityFeature__ft_link__feedpull_import extends Features\UtilityFeature__Abstract {
	const SLUG = 'feedpull-import';

	function __construct() {
		parent::__construct(
			__('Import this source regularly','figurentheater'),
			true,
			Post_Types\Post_Type__ft_link::NAME
		);
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the /Figuren_Theater/src/Network/Post_Types/UtilityFeaturesRepo ;)
