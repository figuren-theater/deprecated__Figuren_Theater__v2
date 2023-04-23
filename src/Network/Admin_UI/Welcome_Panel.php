<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater\inc\EventManager;

class Welcome_Panel implements EventManager\SubscriberInterface
{




// /**
//  * Add a new welcome panel.
//  *
//  * @return string
//  */
// function odin_custom_welcome_panel() {
// 	echo 'Sou o seu novo painel xD';
// 	\remove_action( 'welcome_panel', 'wp_welcome_panel' );
// }

// add_action( 'welcome_panel', __NAMESPACE__. '\\odin_custom_welcome_panel' );


// 	/**
// 	 * Remove the default welcome dashboard message
// 	 * 
// 	 * Helper for Figuren_Theater\Network\Admin_UI\Welcome_Panel 
// 	 * because the Admin_UI Classes are (at all) loaded too late 
// 	 * to remove this action in time.
// 	 */
// function remove_default_welcome_panel(){ 
// 	\remove_action( 'welcome_panel', 'wp_welcome_panel' );
// 	\remove_action( 'welcome_panel', __NAMESPACE__. '\\odin_custom_welcome_panel' );
// }
// \add_action( 'muplugins_loaded', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'init', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( '_admin_menu', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'admin_menu', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'admin_init', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'load-index.php', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'wp', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'admin_notices', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'wp_dashboard_setup', __NAMESPACE__. '\\remove_default_welcome_panel', 10 );
// \add_action( 'wp_dashboard_setup', __NAMESPACE__. '\\remove_default_welcome_panel', 110 );




	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			'muplugins_loaded' => 'remove_default_welcome_panel',
			'welcome_panel' => ['welcome_panel',5],
		);
	}

	/**
	 * Remove the default welcome dashboard message
	 *
	 */
	public function remove_default_welcome_panel()
	{
		\remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}


	/**
	 * Custom welcome panel function
	 *
	 * @access      public
	 * @since       1.0 
	 * @return      void
	 */
	public function welcome_panel()	{

		\remove_action( 'welcome_panel', 'wp_welcome_panel' );

		ob_start();
		\wp_welcome_panel();
		$wp_welcome_panel = ob_get_contents();
		ob_end_clean();

		// $wp_welcome_panel = str_replace( 'WordPress', 'figuren.theater', $wp_welcome_panel );
		// 
		$greeting       = $this->__get_time_based_greeting();
		// $greeting         = \current_time( 'H' );
		$user             = \wp_get_current_user();
		$user_firstname   = ( $user->user_firstname ) ? $user->user_firstname : $user->user_login;
		$welcome_user     = sprintf(
			'<p style="text-transform: uppercase">%s</p>',
			sprintf( 
				__( '%s %s', 'figurentheater' ),
				$greeting,
				$user_firstname
			)
		);
		$wp_welcome_panel = str_replace( '<h2>', $welcome_user . '<h2>', $wp_welcome_panel );

		echo $wp_welcome_panel;

	}

	protected function __get_time_based_greeting() {


		// $hour = date( 'H', time( date_default_timezone_set( 'Europe/Berlin' ) ) ); 
		$hour = (int) \current_time( 'H' ); 
		
		if ($hour > 21)
			return 'Guten Abend';

		if ($hour > 17)
			return 'Hallo';

		if ($hour > 13)
			return 'Schönen Nachmittag';

		if ($hour > 11)
			return 'Mahlzeit';

		if ($hour > 4)
			return 'Guten Morgen';

		return 'Nächtliche Grüße';
	}

}

