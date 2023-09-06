<?php 

namespace Figuren_Theater\FeaturesRepo\Formality;

use Figuren_Theater\FeaturesRepo; // FORM_ID, TEMP_USER_META, POWERED_BY_FIELDS

use Figuren_Theater\Data\Rss_Bridge;
use Figuren_Theater\Network\Blocks;

use Obenland_Wp_Approve_User;

use WP_Error;

use function add_filter;
use function esc_sql;
use function esc_url;
use function has_filter;
use function is_user_logged_in;
use function register_new_user;
use function sanitize_text_field;
use function sanitize_title_with_dashes;
use function update_user_meta;
use function wp_add_inline_style;
use function wp_strip_all_tags;
use function wp_unslash;
use function wp_update_user;

/**
* JS dom events reference
*
* testing for /?722f94eebb6b=oaknut
*
* @see  https://gist.github.com/michelegiorgi/56fe4489b922cf2af4704b79d4f56bb6 Formality hooks reference
*
* 
add_action('wp_print_scripts', function() { ?>
	<script>
		window.addEventListener("foFormsInit", function(e) { console.log(e); })
		// window.addEventListener("foFormSubmit", function(e) { console.log(e); })
		window.addEventListener("foFormSuccess", function(e) { console.log(e); })
		window.addEventListener("foFormError", function(e) { console.log(e); })
		window.addEventListener("foFieldFocus", function(e) { console.log(e); })
		window.addEventListener("foFieldFill", function(e) { console.log(e); })
		// window.addEventListener("foSidebarOpen", function(e) { console.log(e); })
		// window.addEventListener("foSidebarClose", function(e) { console.log(e); })
	</script>
<?php });
*/




function formality_after_validation( array $data ) : void {

	$id = $data['form']['id']; // our registration form
	
	// do check if  we are on the right form, as 'formality_after_validation' runs on every form
	if ( FeaturesRepo\FORM_ID !== $id )
		return;

	// do check if user is NOT logged in
	if ( is_user_logged_in() )
		return;

	// do check if wp-user-approve is loaded !!
	$wpau = Obenland_Wp_Approve_User::get_instance();
	if ( ! $wpau )
		return;
	
	$has_action = has_filter( 'register_new_user', [ $wpau, 'register_new_user' ] );

	if ( $has_action )
		return;
	
	// load our validation rules
	add_filter( 'formality_sanitized_data', __NAMESPACE__ . '\\formality_sanitized_data' );
}




function formality_sanitized_data( array $data ) : array {

	// error_log(var_export($data['fields'],true));
	
	// persist sanitized data for use after approvement
	$user_registration_data             = [];
	$user_registration_data['channels'] = [];

	// all optional fields must be checked
	// to prevent PHP warnings

	// choosen theme slug, delivered from a 'ft themesapi details link'-block
	$theme      = ( isset( $data['fields']['field_722f94eebb6b'] ) ) ? $data['fields']['field_722f94eebb6b']['value'] : ''; // new theme slug

	//
	$url_1      = $data['fields']['field_6e11a1940b57']['value']; // new URL #1
	$url_1_type = ( isset( $data['fields']['field_500e88365617'] ) ) ? $data['fields']['field_500e88365617']['value'] : ''; // new URL #1 type

	$url_2      = $data['fields']['field_f964d7f61d5f']['value']; // new URL #2
	$url_2_type = ( isset( $data['fields']['field_ad5d3950ced4'] ) ) ? $data['fields']['field_ad5d3950ced4']['value'] : ''; // new URL #2 type

	$url_3      = $data['fields']['field_5edc4a267bee']['value']; // new URL #3
	$url_3_type = ( isset( $data['fields']['field_df9d8453f7c9'] ) ) ? $data['fields']['field_df9d8453f7c9']['value'] : ''; // new URL #3 type

	$adr        = $data['fields']['field_dfa47ae53397']['value']; // new imprint-adr
	$first_name = $data['fields']['field_1246b16fde34']['value']; // new user-surname
	$last_name  = $data['fields']['field_0f9b8ba542d7']['value']; // new user-familyname
	$user_login = $data['fields']['field_04868e794072']['value']; // new user-name
	$user_email = $data['fields']['field_f3c7bfc63459']['value']; // new email
	
	$domain = $data['fields']['field_3af389025901']['value']; // home-domain
	$slug   = $data['fields']['field_40f9d74c329a']['value']; // new site-slug
	
	$privacy = $data['fields']['field_23a8682508f1']['value']; // accepted 'privacy' statement
	$tos     = $data['fields']['field_3da19d1ee541']['value']; // accepted 'terms of use' statement
	
	
	// do check the submitted URLs
	if ( ! empty( $theme ) ) {
		// at this point the slug is ok'ish, lets be safe
		$theme = sanitize_title_with_dashes( $theme, '', 'save' );
		// check, this is a real theme
		// if ( ! $theme || empty( $theme ) || ! wp_get_theme( $theme )->exists() ) {
		if ( ! wp_get_theme( $theme )->exists() ) {
			$data['errors'][] = 'Something is wrong with your chosen theme.';
			return $data;
		}
		// everything fine with the theme-slug, save it
		$user_registration_data['theme'] = $theme;
	}
	
	// do check the submitted URLs
	if ( ! empty( $url_1 ) && 'https://' !== $url_1 ) {
		$url_1 = esc_url( $url_1, null, 'db' );
		if ( ! $url_1 ) {
			$data['errors'][] = 'Something is wrong with your submitted channel-URL #1.';
			return $data;
		}
		// everything fine with URL #1, save it
		$user_registration_data['channels'][ $url_1 ] = esc_sql( $url_1_type );
	}

	
	// do check the submitted URLs
	if ( ! empty( $url_2 ) && 'https://' !== $url_2 ) {
		$url_2 = esc_url( $url_2, null, 'db' );
		if ( ! $url_2 ) {
			$data['errors'][] = 'Something is wrong with your submitted channel-URL #2.';
			return $data;
		}
		// everything fine with URL #2, save it
		$user_registration_data['channels'][ $url_2 ] = esc_sql( $url_2_type );
	}


	
	// do check the submitted URLs
	if ( ! empty( $url_3 ) && 'https://' !== $url_3 ) {
		$url_3 = esc_url( $url_3, null, 'db' );
		if ( ! $url_3 ) {
			$data['errors'][] = 'Something is wrong with your submitted channel-URL #3.';
			return $data;
		}
		// everything fine with URL #3, save it
		$user_registration_data['channels'][ $url_3 ] = esc_sql( $url_3_type );
	}

	
	/*
	// do check the submitted URLs
	if ( ! empty( $url_3 ) && 'https://' !== $url_3 ) {
		$url_3 = esc_url( $url_3, null, 'db' );
		if ( ! $url_3 ) {
			$data['errors'][] = 'Something is wrong with your submitted channel-URL #3.';
			return $data;
		}
		// everything fine with URL #3, save it
		$user_registration_data['channels'][ $url_3 ] = esc_sql( $url_3_type );
	}*/

	// do the domain-name-check from our custom block
	$errors = Blocks\validate_domain_request( strtolower( $slug ) );

	// something went wrong, return now
	if (
		isset($errors->validated[0]['errors'])
		&&
		property_exists($errors->validated[0]['errors'], 'errors')
		&&
		!empty($errors->validated[0]['errors']->errors)
		# &&
		# !empty($errors->validated[0]['errors']->errors['blogname'])
	) {
		$data['errors'][] = 'domain_request failed.';
		return $data;
	}
	// at this point the slug is ok'ish, lets be safe
	$user_registration_data['slug']   = sanitize_title_with_dashes( $slug, '', 'save' );
	$user_registration_data['domain'] = esc_sql( $domain );


	// do check the submitted address
	if ( ! empty( $adr ) ) {
		// if ( ! $adr ) {
		// 	$data['errors'][] = 'Something is wrong with your submitted address.';
		// 	return $data;
		// }
		// everything fine with URL #3, save it
		$user_registration_data['adr'] = sanitize_text_field( $adr );
	} 

	// do check for accepted privacy statement
	if ( ! $privacy ) {
		$data['errors'][] = 'You need to know our privacy statement.';
		return $data;
	}


	// do check for accepted tos statement
	if ( ! $tos ) {
		$data['errors'][] = 'Please be friendly and follow our rules.';
		return $data;
	}


	// All checks passed.
	// Let's party and register a new user!


	// Register new user.
	//
	// Do this after the domain_request check,
	// as the check would fail 100% if site and user name should be identical,
	// which could happen sometime ...
	// 
	// cloned from `case 'register':` of
	// https://github.com/WordPress/WordPress/blob/5e58ab8b2ae097f31b6085529bfdf9476cd96b7c/wp-login.php#L1042
	
	// add some helping hand
	$user_login = mb_strtolower( $user_login, 'UTF-8');

	if ( is_string( $user_login ) )
		$user_login = wp_unslash( $user_login );

	if ( is_string( $user_email ) )
		$user_email = wp_unslash( $user_email );

	// register_new_users() houses the do_action('register_new_user'),
	// on which 'Obenland/wp-approve-user' is hooked on.
	// 
	// So WE call this here, 
	// instead of being called within wp-login.php
	// at its switch(action) case: 'register'.
	$new_user = register_new_user( $user_login, $user_email );

	// something went wrong during the pre-registration, return now
	if ( $new_user instanceof WP_Error ) {
		$data['errors'][] = wp_strip_all_tags( $new_user->get_error_message(), true );
		return $data;
	}

	// Set first-name
	if ( is_string( $first_name ) )
		update_user_meta( 
			$new_user,
			'first_name',
			$first_name
		);

	// Set last-name
	if ( is_string( $last_name ) )
		update_user_meta( 
			$new_user,
			'last_name',
			$last_name
		);

	//
	if ( ! empty($first_name) && ! empty($last_name) ) {

		$name = sprintf( '%s %s', $first_name, $last_name );
		wp_update_user( 
			[
				'ID'           => $new_user,
				'display_name' => $name,
				'nickname'     => $name,
			]
		);
	}

	// set the formality result to be authored by its 'author'
	// 
	// the user has no caps on this site
	// so this shouldn't work
	// and ...
	// it doesn't ;)
	/*
	add_filter(
		'wp_insert_post_data',
		function ( array $data, array $postarr, array $unsanitized_postarr, bool $update ) use ( $new_user ) : array {
			
			if ('formality_result' !== $data['post_type']) {
				return $data;
			}

			if (true === $update) {
				return $data;
			}

			// set the formality result to be authored by its 'author'
			// hahaha ;)
			$data['post_author'] = $new_user;

			return $data;
		},
		10,
		4
	);*/

	// persist data, 
	// that was entered during registration
	// until the user is approved
	update_user_meta( 
		$new_user,
		FeaturesRepo\TEMP_USER_META, 
		$user_registration_data
	);

	return $data;
}



function formality_custom_css() {
	// --formality_font: var(--wp--preset--font-family--source-serif-pro);
	// --formality_col2: var(--wp--preset--color--foreground);
	// --formality_col2: var(--wp--preset--color--primary);
	$custom_css = '
		:root {
			--formality_fontsize: inherit;
			--formality_col3: var(--wp--preset--color--primary);
		}

		html body.home {
			--wp--style--global--content-size: 900px;
			overflow-x: hidden;
		}

		body form.fo {
			max-width: var(--wp--style--global--content-size, 650px);
		}
		body form.fo .fo__header {
			margin-bottom: 0;
		}
		body form.fo .fo__main {
			  padding-top: .25em;
		}

		body form.fo .fo__title {
			--formality_font: var(--wp--preset--font-family--source-serif-pro);
			font-weight: normal;
		}
		
		body form.fo .fo__nav__list {
			display: flex;
			justify-content: center;
		}

		body form.fo .fo__nav__list .fo__nav__section:last-child a {
			padding-right: var(--wp--preset--spacing--80, 1.5em);
		}
		body form.fo .fo__nav__list .fo__nav__section:first-child a {
			padding-left: var(--wp--preset--spacing--40, 1.5em);
		}
		
		body form.fo .fo__nav {
			margin-left: calc(-50vw - -50%);
    		width: 100vw;
		}
		
		body form.fo .fo__nav,
		body form.fo .fo__btn {
			--formality_col1: var(--wp--preset--color--primary);
			--formality_col2: var(--wp--preset--color--background);
		}

		/** Bug Fix */

		body form.fo .fo__field--fixed-height.fo__field--focus {
		    height: unset;
		    transition: none;
		}
		body form.fo .fo__field--fixed-height.fo__field--focus:not(.fo__field--disabled) {
		    height: auto;
		    transition: opacity .18s linear;
		}

		body form.fo div.fo__field--disabled > div,
		body form.fo div.fo__field--disabled > label {
		    display: none;
		}
		body form.fo .fo__body .fo__main .fo__section:not(.fo__section--active) {
			left: -30000px;
		}

		';
	wp_add_inline_style( 'formality-public', $custom_css );

	/** Experimenting with social Icons
	body form.fo .fo__field input:checked[value="twitter"] + label.fo__label::after,
	body form.fo .fo__field input[value="twitter"] + label.fo__label::before {
	    background-size: 20%;
	    background-repeat: no-repeat;
	    background-position: 90% 50%;
	    xxbackground-image: url(https://raw.githubusercontent.com/gauravghongde/social-icons/master/SVG/White/Twitter_white.svg);
	    background-image: url(https://raw.githubusercontent.com/gauravghongde/social-icons/master/SVG/Black/Twitter_black.svg);
	}
	body form.fo .fo__field input:checked[value="twitter"] + label.fo__label::after {
	    background-color: var(--formality_col1);
	    xxbackground-image: url(https://raw.githubusercontent.com/gauravghongde/social-icons/master/SVG/Black/Twitter_black.svg);
	    background-image: url(https://raw.githubusercontent.com/gauravghongde/social-icons/master/SVG/White/Twitter_white.svg);
	} */

}


/**
 * TEMP. disabled
 * (for not knowing better)
 * 
 * maybe it's better to save & keep this data,
 * as it contains the users approval on 
 * 
 * 1. privacy terms
 * 2. terms of use
 * 
 * 
 * [__cleanup_formality description]
 *
 * @package [package]
 * @since   3.0
 *
 * @param   string    $user_email [description]
 * @return  [type]                [description]
function __cleanup_formality( string $user_email ) {

	// get all formality-results (PT) by form (TAX)
	// inspired by: formality\admin\class-formality-results.php#421
	$result_query = new WP_Query(
		[
			'cache_results' => false, // as we are going to delete sth. and delete the cache afterwards
			'no_found_rows' => true,
			'post_type'     => 'formality_result',
			'post_status'   => 'any', // important to get posts with an un-registered post_state of 'unread'
			'meta_query'    => [
				[
					'key'   => 'id',
					'value' => FeaturesRepo\FORM_ID,
				],
				[
					'value' => $user_email,
				],
			],
		]
	);
	

	// find the one with our ID
	if ( empty( $result_query->posts ) )
		return;

	// delete formality-result of this users registration
	\wp_delete_post( $result_query->posts[0]->ID, true );
}
 */
/*

class formality {
	// does nothing, but ...
	// helps to trick the old legacy code at: Bootstrap_FeaturesRepo.php to work for now
}
*/


function dynamic_multiple_from_bridges( $type_options, $options) {
	$possible_uids = array_flip( FeaturesRepo\POWERED_BY_FIELDS );

	if ( ! isset($options['uid']) || ! isset($possible_uids[$options['uid']]))
		return $type_options;

	$new_options = $options;

	// 
	$bridges = array_keys( Rss_Bridge\get_bridges() );

	// 
	$new_options['options'] = __convert_to_formality_multiple_options_syntax( $bridges );

	// @TODO
	// for now ...
	// 
	// keep the last element, as it usually contains the generic but funny 'Anything else'-"Button"
	$last_one                 = array_key_last( $options['options'] );
	$new_options['options'][] = $options['options'][ $last_one ];


	$plugin    = new \Formality();
	$fo_fields = new \Formality_Fields(
		$plugin->get_formality(),
		$plugin->get_version(),
	);
	//error_log('########################################    ORIGINAL');
	//error_log($type_options);
	//error_log('########################################    MODIFIED');
	//// error_log($fo_fields->print_multiples( $new_options )); // <inputs only ...
	//error_log($fo_fields->multiple( $new_options )); // <div class="fo__note"></div><div class="fo__input__grid fo__input__grid--buttons fo__input__grid--3">

	// return $type_options;
	return $fo_fields->multiple( $new_options );
}

function __convert_to_formality_multiple_options_syntax(array $options ) : array {
	// what is the best way to get indexes using array_map ?
	// https://stackoverflow.com/questions/5868457/php-can-i-get-the-index-in-an-array-map-function
	return array_map(
		__NAMESPACE__ . '\\__convert_to_fo',
		$options,
		array_keys($options)
	);
}


function __convert_to_fo($v, $k) : array {
	// test to add svg icons inside the value
	$i = '<svg viewbox="0 0 512 512" preserveAspectRatio="xMidYMid meet" width="512" height="512">
          <path d="M344.5 156.9c-38.7 0-72.1 22.1-88.5 54.4 -16.4-32.3-49.8-54.4-88.5-54.4 -54.8 0-99.1 44.4-99.1 99.1 0 54.8 44.4 99.1 99.1 99.1 38.6 0 72.1-22.1 88.5-54.4 16.4 32.3 49.8 54.4 88.5 54.4 54.8 0 99.1-44.4 99.1-99.1C443.6 201.2 399.2 156.9 344.5 156.9zM344.5 328.7c-40.1 0-72.7-32.6-72.7-72.7s32.6-72.7 72.7-72.7 72.7 32.6 72.7 72.7C417.2 296.1 384.6 328.7 344.5 328.7z"></path>
        </svg>';
	// $k = $v;
	// $v = $i . $v;

	return [
        '_key'  => $k,
        'value' => $v,
	];
}
