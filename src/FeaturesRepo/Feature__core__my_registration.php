<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater; // API

use Figuren_Theater\Network\Features;

use DAY_IN_SECONDS;

use function add_action;
use function add_filter;
use function is_admin;
use function site_url;


// @TODO do not hardcode ID
// 
// 793 Andocken & gut
// 796 Theater online

const FT_LEVEL_TO_INSTALL_FIRST = 793; // The 'ft_level' post->ID from websites.fuer.figuren.(.theater|.test) to use as blueprint.

// const FORM_ID = 95; // formality post 'Anmelden & starten' at mein.figuren.test
const FORM_ID = 219; // formality post 'Andocken & gut' at mein.figuren.test

const POWERED_BY_FIELDS = [
	'500e88365617', // Was treibt deine URL an?
	'ad5d3950ced4', // Was treibt deine 2. URL an?
	'df9d8453f7c9', // Was treibt deine 3. URL an?
];

const TEMP_USER_META = '_create_site_with'; 



/**
 * 
 */
class Feature__core__my_registration extends Features\Feature__Abstract {

	const SLUG = 'core-my-registration';

	function __construct() {

		$this->non_default_options = [

			##########################################
			# HIDDEN defaults
			##########################################
			'users_can_register' => 1,

		];
	}


	public function enable() : void {

		add_action( 'Figuren_Theater\loaded', [$this,'modify_options'], 12 ); // after core added its options

		add_filter( 
			'Figuren_Theater.config', 
			function ( array $config ) : array {
				$config['modules']['interactive']['formality']      = true;
				$config['modules']['onboarding']['wp-approve-user'] = true;
				return $config;
			}
		);


		/////////////////////////////////
		// STUFF FROM 'mu_per_blog/12' //
		/////////////////////////////////

		add_action( 'init', function () {

			// functions called within this workflow
			// but related to clear plugins or core-parts
			require_once dirname( __DIR__ ) . '/FeaturesAssets/' . self::SLUG . '/wp_core.php';
			require_once dirname( __DIR__ ) . '/FeaturesAssets/' . self::SLUG . '/formality.php';
			require_once dirname( __DIR__ ) . '/FeaturesAssets/' . self::SLUG . '/wp_approve_user.php';

			// Change Registration URL
			add_filter( 'register_url', __NAMESPACE__ . '\\WP_Core\\register_url' );
			
			// 0. modify form
			add_filter( 'formality_form_field',__NAMESPACE__ . '\\Formality\\dynamic_multiple_from_bridges', 10, 2 );

			// 1. hand registration details to approving-process
			add_action( 'formality_after_validation', __NAMESPACE__ . '\\Formality\\formality_after_validation' );

			//
			add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\Formality\\formality_custom_css', 11 );

			//
			add_filter( 'password_reset_expiration', __NAMESPACE__ . '\\WP_Core\\password_reset_expiration' );


			/**
			 * Disable the default Email with a link to set a password.
			 * 
			 * @see https://github.com/johnbillion/wp_mail#a-new-user-is-created
			 */
			add_filter( 'wp_new_user_notification_email', __NAMESPACE__ . '\\WP_Core\\new_user_notification_email', 10, 3 );


			// 2. Approve user
			
			// 3. Create site for user (if wanted)
			// 
			// Runs before the normal approvement routine runs 
			// and the "new user is approved" email is sent
			// where we need the site_id of the created website.
			add_action( 'wpau_approve', __NAMESPACE__ . '\\WP_Core\\site_creation_from_formality_registration', 0 );

			// // Modify the typical "Approve user" quicklink, 
			// // to "Approve user & create site XYZ", 
			// // if the pending user came from a site-registration form
			add_filter( 'user_row_actions', __NAMESPACE__ . '\\WP_Approve_User\\user_row_actions', 11, 2 );
			add_filter( 'ms_user_row_actions', __NAMESPACE__ . '\\WP_Approve_User\\user_row_actions', 11, 2 );

					
		});

	}

	public function modify_options() {


		// 1.
		// new Options\Factory( $this->non_default_siteoptions, 'Figuren_Theater\Options\Option', 'core', 'site_option' );
		
		// 2.
		array_map( 
			function( $option_name, $option )
			{
				$handled_option = Figuren_Theater\API::get('Options')->get( "option_{$option_name}" );
				if ( null !== $handled_option )
					$handled_option->set_value( $option );
			},
			array_keys( $this->non_default_options ),
			$this->non_default_options
		);
	}


	public function enable__on_admin() : void {}


	public function disable() : void {}


}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
