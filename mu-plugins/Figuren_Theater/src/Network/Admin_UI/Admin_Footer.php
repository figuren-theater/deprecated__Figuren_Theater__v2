<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater;
use Figuren_Theater\inc\EventManager;

class Admin_Footer implements EventManager\SubscriberInterface
{


	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			// Admin Footer Text on the left
			// 'admin_footer_text' => ['admin_footer_text', 100 ],
			// Admin Footer Text on the right
			'update_footer'     => ['update_footer', 100 ],
		);
	}



	/**
	 * Filters the "Thank you" text displayed in the admin footer.
	 *
	 * @package Figuren_Theater
	 * @version 2022.04.09
	 * @author  Carsten Bach
	 *
	 * @since   WP 2.8.0
	 * 
	 * @param   string       $text The text that will be printed.
	 * 
	 * @return  string             The text that will be printed.
	 */
	public static function admin_footer_text( string $text ) : string
	{
		return sprintf(
			'<span id="footer-thankyou">Developed By:<a href="http://rainydaymedia.net" target="_blank" title="Rainy Day Media, LLC.">Rainy Day Media, LLC.</a></span>',
		);
	}



	/**
	 * Filters the version/update text displayed in the admin footer.
	 *
	 * WordPress prints the current version and update information,
	 * using core_update_footer() at priority 10.
	 *
	 * @package Figuren_Theater
	 * @version 2022.04.09
	 * @author  Carsten Bach
	 *
	 * @since WP 2.3.0
	 *
	 * @see   core_update_footer()
	 *
	 *
	 * @param   string       $text The text that will be printed.
	 * 
	 * @return  string             The text that will be printed.
	 */
	public static function update_footer( string $text ) : string
	{
		return sprintf(
			_x(
				'Your %1$s has Version %2$s and is made with 💕 and WordPress %3$s',
				'%1$s = website, %2$s = version number, %3$s WordPress Version Number',
				'figurentheater'
			),
			'<em>website.fuer.figuren.theater</em>',
			Figuren_Theater\get_platform_version(),
			$text
		);
	}

}

