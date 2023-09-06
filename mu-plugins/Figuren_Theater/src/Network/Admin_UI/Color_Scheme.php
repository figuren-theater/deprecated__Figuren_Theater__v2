<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater\inc\EventManager;

/**
 * Makes a nice figuren.theater-alike UI Branding 
 *
 * @see https://wpadmincolors.com/export/figurentheater
 * @see https://codepen.io/carstenbach/pen/LYbjMeE
 * @see https://cssshrink.com/
 *
 * Understand using this WP feature by reading this: !!!
 *
 * @see https://shellcreeper.com/how-to-remove-wp-admin-color-scheme-option/
 */
class Color_Scheme implements EventManager\SubscriberInterface {
	
	const NAME = 'figurentheater';

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array {
		return [
			'network_admin_menu' => 'render_admin_color_scheme',
			'user_admin_menu'    => 'render_admin_color_scheme',
			'admin_menu'         => 'render_admin_color_scheme',
			// 'admin_init'         => 'render_admin_color_scheme',
			// 'admin_bar_menu'         => 'render_admin_color_scheme',
		];
	}


	public function render_admin_color_scheme() : void {
		$theme_dir = \WPMU_PLUGIN_URL . '/Figuren_Theater/assets/css';

		// figuren.theater5
		\wp_admin_css_color( 
			$this::NAME, 
			__( 'figuren.theater', 'figurentheater' ),
			$theme_dir . '/ft_admin-scheme5.css',
			[ '#0f0b0e', '#fbf9fa', '#3e58e1', '#d20394' ],
			#array(
			#	'base'    => '#6d024d',
			#	'focus'   => '#fff',
			#	'current' => '#fff',
			#)
		);

	}

}

