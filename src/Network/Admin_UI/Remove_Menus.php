<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\inc\EventManager as Inc;



class Remove_Menus implements Inc\SubscriberInterface
{


	/**
	 * 'action'-property
	 * 
	 * @var array
	 */
	protected $remove_menus = [];


	function __construct( array $menus_to_remove )
	{
		$this->remove_menus   = $menus_to_remove;
		
		// if ('local' !== \WP_ENVIRONMENT_TYPE)
			$this->remove_menus[] = [
				'plugins.php' => 'plugin-install.php', // was accessible even with ms-option to "not show plugin-menus to admins"
				// 'tools.php'   => 'import.php',

				/**
				 * [$remove_my_sites]
				 * maybe re-enable when user has "big" network?.
				 * 
				 * @var Admin_UI
				 */
				'index.php'   => 'my-sites.php',
			];

	}

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			'admin_menu' => [ 'remove_admin_menus', 9999 ],
		);
	}

	public function remove_admin_menus()
	{
		if ( empty( $this->remove_menus ))
			return;

		// otherwise
		// remove menus for plugins, handled by _ft-plugins-management
		foreach ($this->remove_menus as $menu_to_remove) {
			foreach ($menu_to_remove as $i => $v) {
				if (is_int($i)) {
					\remove_menu_page($v);
				} else {
					\remove_submenu_page($i,$v);
				}
			}
		}
	}

}
