<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater\inc\EventManager;

class Welcome_Panel implements EventManager\SubscriberInterface
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
		$this->styles();
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

	public function styles() {
		?>
<style type="text/css">
	.welcome-panel{background-color:var(--wp-admin-theme-color-darker-10, #a00271)}
	.welcome-panel-header-image path{fill:var(--wp-admin-theme-color-darker-20, #6d024d)!important;stroke: #d20394}
	.welcome-panel .welcome-panel-column-container{background:#fbf9fa}
</style>
	<?php
	}

}

