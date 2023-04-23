<?php 

namespace Figuren_Theater\FeaturesRepo\WP_Core;

use Figuren_Theater\FeaturesRepo; // TEMP_USER_META, FT_LEVEL_TO_INSTALL_FIRST
use Figuren_Theater\UtilityFeaturesRepo;

use FT_CORESITES;

use Figuren_Theater;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\Options;

use WP_Error;
use WP_User;
use WP_Query;

use function __;
use function add_action;
use function add_filter;
use function add_query_arg;
use function apply_filters;
use function delete_user_meta;
use function esc_url;
use function get_blogs_of_user;
use function get_current_blog_id;
use function get_current_site;
use function get_home_url;
use function get_option;
use function get_password_reset_key;
use function get_site;
use function get_site_url;
use function get_user_locale;
use function get_userdata;
use function is_wp_error;
use function network_site_url;
use function remove_action;
use function remove_user_from_blog;
use function restore_current_blog;
use function restore_previous_locale;
use function sanitize_text_field;
use function switch_to_blog;
use function switch_to_locale;
use function update_option;
use function url_shorten;
use function wp_get_theme;
use function wp_insert_post;
use function wp_insert_site;
use function wp_login_url;
use function wp_set_object_terms;

/////////////////////////////////
// STUFF FROM 'mu_per_blog/12' //
/////////////////////////////////

/**
 * Change Registration URL
 *
 * Make sure to point all links to our custom form
 * currently on the frontpage at https://mein.figuren.test
 * ant not use the default links like:
 * `site_url( 'wp-login.php?action=register', 'login' )`
 *
 * Only change the URL when not viewing an admin page.
 *
 * @package [package]
 * @since   2.10
 *
 * @param  string $url [description]
 * 
 * @return [type]      [description]
 */
function register_url( string $url ) : string {

	if ( is_admin() ) {
		return $url;
	}
	return site_url(); // currently on the frontpage at https://mein.figuren.test
}


/**
 * Defines '2 days' as the password_reset_expiration time 
 * for the reason of fairness, similar to the time, we take for user-approvement.
 *
 * @package [package]
 * @since   3.0
 *
 * @param   int $exp_in_seconds default expiration time, typically ...
 * @return  int                 changed expiration time of 2 days
 */
function password_reset_expiration( int $exp_in_seconds ) : int {
	return 2 * DAY_IN_SECONDS;
}


function site_creation_from_formality_registration( int $user_id ) : void {
		
	$user = get_userdata( $user_id );

	if ( ! $user instanceof WP_User )
		return;

	// get temporary data, collected during registration
	$user_registration_data = $user->get( FeaturesRepo\TEMP_USER_META );

	if ( ! is_array( $user_registration_data ) )
		return;

	if ( empty( $user_registration_data ) )
		return;

	
	// create wordpress blog
	if ( ! __create_ft_level_zero_blog( $user, $user_registration_data ) )
		return;


	// TEMP. disabled
	// (for not knowing better)
	// 
	// maybe it's better to save & keep this data,
	// as it contains the users approval on 
	// 
	// 1. privacy terms
	// 2. terms of use
	// 
	// __cleanup_formality( $user->user_email );
}


function __create_ft_level_zero_blog( WP_User $user, array $user_registration_data ) : bool {


	// Does the user wanted an own website during registration?
	if ( ! isset( $user_registration_data['slug'] ) || ! isset( $user_registration_data['domain'] ) )
		return false;

	// get site_id (aka the ID of the network ;) 
	// of mein.figuren.theater,
	// which we know only the blog_id from.
	$mein_ft_network = (int) get_site( array_flip( FT_CORESITES )['mein'] )->site_id;

	$_wp_insert_site_args = [
		'domain'     => $user_registration_data['domain'],
		'path'       => '/' . $user_registration_data['slug'],
		'network_id' => $mein_ft_network,
		'public'     => 1,

		'user_id'    => $user->ID,
		// if this 'rebuild' of the site-url is gone after installation 
		// and instead, it says the "User Nicename",
		// everything is very cool and ok.
		'title'      => $user_registration_data['domain'] . '/' . $user_registration_data['slug'], 
		// 'title'      => $user->user_nicename,

		'ft_level'   => FeaturesRepo\FT_LEVEL_TO_INSTALL_FIRST,

		'options' => [
			// ' send_activation_email' => true|false
		],
		
	];

	// already validated
	// in \FeaturesAssets\core-my-registration\formality.php
	if ( isset( $user_registration_data['theme'] ) ) {
		$_wp_insert_site_args['options']['template']   = $user_registration_data['theme'];
		$_wp_insert_site_args['options']['stylesheet'] = $user_registration_data['theme'];
	}

	// regsiter a pending site for the user, if wanted
	$site_created = wp_insert_site( $_wp_insert_site_args );
	
	if ( $site_created instanceof WP_Error )
		return false;

	//
	switch_to_blog( $site_created );
		
		// add "channels-to-connect" as ft_link-posts
		__create_ft_links( $user, $user_registration_data );
		
	//
	restore_current_blog();

	//
	__cleanup_registration( $user->ID );

	return true;
}

function __create_ft_links( WP_User $user, array $user_registration_data ) : bool {


	// Does the user wanted to connect some channels during registration?
	if ( ! isset( $user_registration_data['channels'] ) || empty( $user_registration_data['channels'] ) )
		return false;

	array_walk(
		$user_registration_data['channels'], 
		__NAMESPACE__ . '\\__create_ft_link_post',
		$user
	);

	return true;
}


function __create_ft_link_post( string $type, string $url, WP_User $user ) : void {

	$url = esc_url( sanitize_text_field( $url ) );
	if ( ! $url ) {
		return;
	}

	$url_short = url_shorten( $url );

	// prepare a wp_insert_post-compatible 
	// array of post-data
	$ft_link = new Post_Types\Post_Type__ft_link( [
		'user_id'          => $user->ID,
		'new_post_title'   => $url_short,
		'new_post_content' => $url,
	] );
	// get all the defaults for that PT
	$data = $ft_link->get_post_data();

	// save the user-suggested platform into post_meta
	// this will be picked up by our RSS-Bridge detector at
	// ft-data\inc\feed-pull\auto-setup.php
	if (!empty($type)) {
		$data['meta_input'] = [];
		$data['meta_input'][] = [ '_ft_platform' => $type ];
	}

	// create post
	// @TODO could use ft_query->save() here
	$ft_link_id = wp_insert_post( $data );

	if ( ! is_int( $ft_link_id ))
		return;

	$_link_categories = [
		Taxonomies\Term__link_category__own::SLUG,
		Taxonomies\Term__link_category__imprint::SLUG,
	];

	// if ( __is_ft_link_privacy_relevant( $url_short ) ) {
	if ( Post_Types\Post_Type__ft_link::__is_privacy_relevant( $url_short ) ) {
		$_link_categories[] = Taxonomies\Term__link_category__privacy::SLUG; // only for 3rd-parties
	}

	// Add terms for 'Own content' and 'Imprint'
	// to all of them
	wp_set_object_terms( 
		$ft_link_id, 
		$_link_categories,
		Taxonomies\Taxonomy__link_category::NAME
	);

	// find RSS endpoints 
	// based on:
	// 1. the type, the user gave us during registration
	// 2. url-schemes
	// 
	// NO:  DO THIS BY HOOKING INTO save_post_ft_link, which is triggered upstairs
	// YES: Trigger this by setting the tax-term now
	wp_set_object_terms( 
		$ft_link_id, 
		UtilityFeaturesRepo\UtilityFeature__ft_link__feedpull_import::SLUG, //'feedpull-import',
		Features\UtilityFeaturesManager::TAX
	);
}


function __cleanup_registration( int $user_id ) {

	// Delete meta-data persisted during user-registration
	delete_user_meta( $user_id, FeaturesRepo\TEMP_USER_META );

	// Remove the user from the network_site,
	// where she or he was registered in the first time
	// without an own website.
	remove_user_from_blog( $user_id, get_current_blog_id() );
}



function new_user_notification_email( array $wp_new_user_notification_email, WP_User $user, string $blogname ) : array {

	$_has_blogs = get_blogs_of_user( $user->ID );

	// remove the network_site 
	// from the list of allowed sites to switch to
	unset( $_has_blogs[ get_current_site()->blog_id ] );
	
	/*
	wp_die( '<pre>'.var_export(
		[
			'######################### '. __FILE__,
			__nune_message( $user, $_has_blogs ),
			\get_current_site(),
			$_has_blogs,
			$wp_new_user_notification_email,
			$user,
			$blogname,
		],
		true
	).'</pre>' );*/

	// $wp_new_user_notification_email['to'] = ;
	$wp_new_user_notification_email['subject'] = "[$blogname] ist startklar!";
	$wp_new_user_notification_email['message'] = __nune_message( $user, $_has_blogs );
	// $wp_new_user_notification_email['headers'] = ;

	// wp_die($wp_new_user_notification_email['message']);
	return $wp_new_user_notification_email;
}


function __nune_message( WP_User $user, array $_has_blogs ) : string {

	$switched_locale = switch_to_locale( get_user_locale( $user ) );

	if ( ! empty( $_has_blogs ) ) {

		$_new_stuff = '<strong>Deine Anmeldung war erfolgreich 🎉 und deine neue Website 🥳 💕 ist startklar unter:</strong> 
	' . get_site_url( array_key_first( $_has_blogs ), '/', 'https' );

		$_login_url = get_home_url( array_key_first( $_has_blogs ), getenv( 'FT_SECURITY_LOGIN_SLUG' ) . '/' );

	} else {

		$_new_stuff = '<strong>Deine Anmeldung war erfolgreich.</strong> 🎉 🥳 💕';
		$_login_url = wp_login_url();
	}



	$message  = sprintf(
		'<h1 style="text-transform: uppercase;"><em style=" color:#d20394;">Hi</em> %s</h1>',
		$user->user_login
	) . "\r\n";
	$message .= $_new_stuff . "\r\n\r\n";
	$message .= __email_rp_link( $user );

	// $message .= __( '🌟 For later use, bookmark this URL as your Login-Page and do not share it with anybody!', 'figurentheater' ) . "\r\n";
	$message .= __( '🌟 Speichere diese Adresse als Login-Seite und teile sie mit niemandem!', 'figurentheater' ) . "\r\n";
	$message .= $_login_url . "\r\n\r\n";

	$message .= 'Falls du Fragen hast, oder Probleme, scheue nicht Kontakt mit uns aufzunehmen.
Wir wollen ja schließlich auch, dass das hier alles gut wird!' . "\r\n\r\n";

	$message .= __( 'Beste Grüße', 'figurentheater' ) . "\r\n";
	$message .= 'Julia & Carsten' . "\r\n\r\n";

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	return $message;
}


function __email_rp_link( WP_User $user ) : string {

	$key = get_password_reset_key( $user );
	if ( is_wp_error( $key ) ) {
		return '';
	}

	/**
	 * Values of add_query_arg() are expected to be encoded 
	 * appropriately with urlencode() or rawurlencode().
	 *
	 * Using rawurlencode on any variable used as part of the query string, 
	 * either by using add_query_arg() or directly by string concatenation, 
	 * will prevent parameter hijacking.
	 *
	 * @see  https://docs.wpvip.com/technical-references/code-quality-and-best-practices/encode-values-passed-to-add_query_arg/
	 */
	$args       = array_map( 
		'rawurlencode',
		[
			'action' => 'rp',
			'key'    => $key,
			'login'  => $user->user_login,
		]
	);
	$login_url = add_query_arg( 
		$args, 
		network_site_url( 'wp-login.php', 'login' )
	);

	/* translators: %s: User login. */ //Unter der folgenden Adresse kannst du dein Passwort festlegen:
	// $message  = __( 'You have to set your password, visit the following address:' ) . "\r\n";
	$message  = __( 'Unter der folgenden Adresse kannst du dein Passwort festlegen:' ) . "\r\n";
	// $message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . "\r\n";
	$message .= $login_url . "\r\n";

	$_pwret = (int) apply_filters( 'password_reset_expiration', 1 );
	$_pwret = $_pwret / 3600;

	$message .= sprintf(
		// __( '<em>This link will be active for %s hours.</em>', 'figurentheater' ) . "\r\n\r\n",
		__( '<em>Dieser Link ist %s Stunden lang gültig.</em>', 'figurentheater' ) . "\r\n\r\n",
		$_pwret
	);

	return $message;
}

/*
class WP_Core {
	// does nothing, but ...
	// helps to trick the old legacy code at: Bootstrap_FeaturesRepo.php to work for now
}
*/
