<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;



class Feature__eigene_domain extends Features\Feature__Abstract
{

	const SLUG = 'eigene-domain';


	public function enable() : void 
	{
		//
		$this->non_default_options = [
			'blog_upload_space' => 8000,
		];

		\add_action( 'Figuren_Theater\loaded', [$this,'modify_options'], 12 ); // after the plugin added its option
	}

	public function modify_options()
	{

		array_map( 
			function( $option_name, $option )
			{
				$handled_option = \Figuren_Theater\API::get('Options')->get( "option_{$option_name}" );
				if ( null !== $handled_option )
					$handled_option->set_value( $option );
			},
			array_keys( $this->non_default_options ),
			$this->non_default_options
		);
	}

	public function enable__on_admin() : void {}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
