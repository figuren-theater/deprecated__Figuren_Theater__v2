<?php
declare(strict_types=1);

namespace Figuren_Theater\UtilityFeaturesRepo;

// use Figuren_Theater\Coresites\Post_Types as Coresites_Post_Types;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;

/**
 * 
 */
class UtilityFeature__ft_production__is_being_played extends Features\UtilityFeature__Abstract
{
	const SLUG = 'is-being-played';

	function __construct()
	{
		parent::__construct( 
			__('Is being played.','figurentheater'),
			true,
			Post_Types\Post_Type__ft_production::NAME
		);
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the /Figuren_Theater/src/Network/Post_Types/UtilityFeaturesRepo ;)
