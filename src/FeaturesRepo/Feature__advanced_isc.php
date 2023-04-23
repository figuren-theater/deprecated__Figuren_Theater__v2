<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\PluginsRepo;

use Figuren_Theater\Network\Features;



class Feature__advanced_isc extends Features\Feature__Abstract
{

	const SLUG = 'advanced-isc';


	function __construct()
	{
		// $this->option_name = \PluginsRepo\image_source_control_isc->option_name
		$this->option_name = 'isc_options';

		$this->non_default_options = [

			##########################################
			# 
			##########################################
			'warning_onesource_missing' => true, // will be changed by Feature 'advanced-isc'
			'licences' => 'All Rights Reserved
				Public Domain Mark 1.0|https://creativecommons.org/publicdomain/mark/1.0/
				CC0 1.0 Universal|https://creativecommons.org/publicdomain/zero/1.0/
				CC BY 4.0 International|https://creativecommons.org/licenses/by/4.0/
				CC BY-SA 4.0 International|https://creativecommons.org/licenses/by-sa/4.0/
				CC BY-ND 4.0 International|https://creativecommons.org/licenses/by-nd/4.0/
				CC BY-NC 4.0 International|https://creativecommons.org/licenses/by-nc/4.0/
				CC BY-NC-SA 4.0 International|https://creativecommons.org/licenses/by-nc-sa/4.0/
				CC BY-NC-ND 4.0 International|https://creativecommons.org/licenses/by-nc-nd/4.0/
				CC BY 3.0 Unported|https://creativecommons.org/licenses/by/3.0/
				CC BY-SA 3.0 Unported|https://creativecommons.org/licenses/by-sa/3.0/
				CC BY-ND 3.0 Unported|https://creativecommons.org/licenses/by-nd/3.0/
				CC BY-NC 3.0 Unported|https://creativecommons.org/licenses/by-nc/3.0/
				CC BY-NC-SA 3.0 Unported|https://creativecommons.org/licenses/by-nc-sa/3.0/
				CC BY-NC-ND 3.0 Unported|https://creativecommons.org/licenses/by-nc-nd/3.0/
				CC BY 2.5 Generic|https://creativecommons.org/licenses/by/2.5/
				CC BY-SA 2.5 Generic|https://creativecommons.org/licenses/by-sa/2.5/
				CC BY-ND 2.5 Generic|https://creativecommons.org/licenses/by-nd/2.5/
				CC BY-NC 2.5 Generic|https://creativecommons.org/licenses/by-nc/2.5/
				CC BY-NC-SA 2.5 Generic|https://creativecommons.org/licenses/by-nc-sa/2.5/
				CC BY-NC-ND 2.5 Generic|https://creativecommons.org/licenses/by-nc-nd/2.5/
				CC BY 2.0 Generic|https://creativecommons.org/licenses/by/2.0/
				CC BY-SA 2.0 Generic|https://creativecommons.org/licenses/by-sa/2.0/
				CC BY-ND 2.0 Generic|https://creativecommons.org/licenses/by-nd/2.0/
				CC BY-NC 2.0 Generic|https://creativecommons.org/licenses/by-nc/2.0/
				CC BY-NC-SA 2.0 Generic|https://creativecommons.org/licenses/by-nc-sa/2.0/
				CC BY-NC-ND 2.0 Generic|https://creativecommons.org/licenses/by-nc-nd/2.0/',
			'standard_source_text' => '© Demos für websites.fuer.figuren.theater',

		];

	}
	public function enable() : void 
	{

		// Make sure, this is loaded
		\Figuren_Theater\API::get('Plugins')->add_to( 
			new PluginsRepo\image_source_control_isc,
			'feature_required'
		);
		\add_action( 'Figuren_Theater\loaded', [$this,'modify_options'], 12 ); // after the plugin added its option
	}

	public function modify_options()
	{
		$isc_option = \Figuren_Theater\API::get('Options')->get( "option_{$this->option_name}" );
		$advanced_isc_options = array_merge(
			$this->non_default_options,
			$isc_option->value // current defaults, set in PluginsRepo\image_source_control_isc
		);
		// update ISC options
		$isc_option->set_value( $advanced_isc_options );

	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
