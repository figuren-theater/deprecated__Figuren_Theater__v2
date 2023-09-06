<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI\Traits;

use Figuren_Theater\Network\Admin_UI;

trait Has_Notice_Rule {

	/**
	 * Implements the basics for adding admin-notices
	 * into the Admin-UI
	 *
	 * Required by Admin_UI\Interfaces\Has_Notice_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Traits
	 * @version 2.10.20
	 * @author  Carsten Bach
	 *
	 * @return  Admin_Notice[]|[]       list of 'Admin_Notice' objects or an empty array to disable the notice
	 */
	public function get_notices() : array {
		return [ 
			new Admin_UI\Admin_Notice(
				sprintf( 
					_x( 'Settings are managed for the whole network by %s', '%s = Plugin Name', 'figurentheater' ),
					'<em>' . self::class . '</em>'
				),
				'is-dismissible info'
			),
		];
	}


	/**
	 * Implements the 'until-this'-capability.
	 * All users below this, will see the admin-notice.
	 *
	 * Required by Admin_UI\Interfaces\Has_Notice_Rule
	 *
	 * @package Figuren_Theater\Network\Admin_UI\Traits
	 * @version 2.10.20
	 * @author  Carsten Bach
	 *
	 * @return  String       WordPress capability
	 */
	public function get_needed_cap() : string {
		return $this->needed_cap;
	}

}
