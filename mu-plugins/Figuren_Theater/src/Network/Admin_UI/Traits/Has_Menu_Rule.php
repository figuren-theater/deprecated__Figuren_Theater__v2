<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI\Traits;

trait Has_Menu_Rule {

	/**
	 * Implements the basics for removing Menus
	 * from the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Menu_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Traits
	 * @version 2.10.20
	 * @author  Carsten Bach
	 *
	 * @return  Array       list of menu slugs
	 */
	public function get_menus_to_remove() : array {
		return [];
	}


	/**
	 * Implements the capabilities needed
	 * for removing Menus from the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Menu_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Traits
	 * @version 2.10.20
	 * @author  Carsten Bach
	 *
	 * @return  String       WordPress capability
	 */
	public function get_menus_to_remove_cap() : string {
		return '';
	}

}
