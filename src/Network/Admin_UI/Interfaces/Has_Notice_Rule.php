<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI\Interfaces;

interface Has_Notice_Rule extends Has_Rule {

	/**
	 * Implements the basics for adding admin-notices
	 * into the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Notice_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Interfaces
	 * @version 2022.05.31
	 * @author  Carsten Bach
	 *
	 * @return  Admin_Notice[]|[]       list of 'Admin_Notice' objects or an empty array to disable the notice
	 */
	public function get_notices() : array;


	/**
	 * Implements the 'until-this'-capability.
	 * All users below this, will see the admin-notice.
	 *
	 * Required by Admin_UI\Interfaces\Has_Notice_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Interfaces
	 * @version 2022.05.31
	 * @author  Carsten Bach
	 *
	 * @return  String       WordPress capability
	 */
	public function get_needed_cap() : string;
}
