<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI\Interfaces;

interface Has_Menu_Rule extends Has_Rule {

	/**
	 * Implements the basics for removing Menus
	 * from the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Menu_Rule
	 *
	 * @package Admin_UI
	 * @version 2022.05.31
	 * @author  Carsten Bach
	 *
	 * @return  Array       list of menu slugs
	 */
	public function get_menus_to_remove() : array;


	/**
	 * Implements the capabilities needed
	 * for removing Menus from the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Menu_Rule
	 *
	 * @package Admin_UI
	 * @version 2022.05.31
	 * @author  Carsten Bach
	 *
	 * @return  String       WordPress capability
	 */
	public function get_menus_to_remove_cap() : string;
}
