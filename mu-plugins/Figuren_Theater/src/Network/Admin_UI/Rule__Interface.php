<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\SiteParts as SiteParts;


interface Rule__Interface
{
	/**
	 * Returns minimum capability to implement this Rule.
	 * @return          String        WP_capability
	 */
	public function get_minimum_capability() : string;
	

	public function can_implement() : bool;

	public function without_cap( SiteParts\SitePartsManagerInterface $Admin_UIManager ) : void;

	public function with_cap( SiteParts\SitePartsManagerInterface $Admin_UIManager ) : void;

}
