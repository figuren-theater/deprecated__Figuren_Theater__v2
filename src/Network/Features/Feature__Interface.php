<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;



interface Feature__Interface
{
	/**
	 * unique slug used as term_slug inside the 'ft_feature_shadow' TAX
	 * 
	 * @var string
	 */
	public function get_slug() : string;

	/**
	 * Method that will be called by the FeaturesManager
	 * when Feature is wanted by and 
	 * available for the current website.
	 * 
	 * @return   none
	 */
	public function enable() : void;

	public function enable__on_admin() : void;

	/**
	 * Method that will be called by the FeaturesManager
	 * when Feature is unwanted by or 
	 * not available for the current website.
	 * 
	 * @return   none
	 */
	public function disable() : void;

}
