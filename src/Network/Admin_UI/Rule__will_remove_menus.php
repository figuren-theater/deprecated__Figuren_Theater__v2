<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;



class Rule__will_remove_menus extends Rule__Abstract implements Rule__will_remove_menus__Interface 
{


	protected $remove_menus = [];

	function __construct( String $minimum_capability, Array $remove_menus )
	{
		$this->minimum_capability = $minimum_capability;
	
		// if ( isset( $args['remove_menus']) && is_array( $args['remove_menus'] ) )
			// $this->remove_menus     = $args['remove_menus'];
			$this->remove_menus     = $remove_menus;
	}


	public function remove_menus() : array
	{
		return $this->remove_menus;
	}

}
