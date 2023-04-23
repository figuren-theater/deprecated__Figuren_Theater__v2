<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Features;



class Feature__nicht_oeffentliche_probe extends Features\Feature__Abstract implements EventManager\SubscriberInterface
{

	const SLUG = 'nicht-oeffentliche-probe';

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			'template_redirect' => 'redirect_public_users',

		);
	}

	public function enable() : void {}
	public function enable__on_admin() : void {}


	public function redirect_public_users() {

		// only redirect users LIVE
		if ( 'production' !== WP_ENVIRONMENT_TYPE )
			return;

		// do nothing, if we're on the login
		if ( $_SERVER['REQUEST_URI'] === \esc_url( \wp_login_url() ) )
			return;

		if ( \is_user_logged_in() )
			return;


		\Figuren_Theater\API::get('Options')->get( "option_blog_public" )->set_value( 0 );

		// // get settings via customizer
		// $theme_mods = get_theme_mods();
		// if ( "1" === $theme_mods['hide_website'] )
		// 	$url = esc_url( $theme_mods['hide_website_url'] );
		// else
		// 	return;

		// $url = \get_home_url( null, '', 'https' );

		// fallback
		// if ( empty( $url ) )
			$url = 'https://websites.fuer.figuren.theater/';

		if ( \wp_redirect( $url ) ) {
			exit;
		}
	}


}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
