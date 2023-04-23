<?php
declare(strict_types=1);

namespace Figuren_Theater\UtilityFeaturesRepo;

// use Figuren_Theater\Coresites\Post_Types as Coresites_Post_Types;

use Figuren_Theater\Network\Features;
// use Figuren_Theater\Network\Post_Types;

/**
 * Replacement for is_ft_core_site('mein')
 */
class UtilityFeature__ft_site__type__performing_arts_group extends Features\UtilityFeature__Abstract
{
	const SLUG = 'type-performing-arts-group';

	function __construct()
	{
		parent::__construct(
			__('We are a performing arts group.','figurentheater'),
			false
		);
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the /Figuren_Theater/src/Network/Post_Types/UtilityFeaturesRepo ;)
