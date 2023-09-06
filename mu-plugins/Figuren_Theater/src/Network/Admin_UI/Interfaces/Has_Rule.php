<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI\Interfaces;

interface Has_Rule {

	/**
	 * Implements the basics for doing 
	 * changes to the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Interfaces
	 * @version 2022.05.31
	 * @author  Carsten Bach
	 *
	 * @return  string[]       list of screen IDs
	 */
	public function get_related_screen_ids() : array;
}
