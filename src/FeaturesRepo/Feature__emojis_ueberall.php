<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;


class Feature__emojis_ueberall extends Features\Feature__Abstract {

	const SLUG = 'emojis-ueberall';

	public function enable() : void {
		// do nothing
		// and stay with the defaults
		// so Emojis will be visible
		// for the cost of performance
		// 
		// and additionally 
		// 1. add 'compressed emoji'
		// 2. add 'emoji picker' block
		\add_filter( 
			'figuren_theater.config', 
			function ( array $config ) : array {
				$config['modules']['privacy']['compressed-emoji'] = true;
				$config['modules']['admin_ui']['emoji-toolbar']   = true;
				return $config;
			}
		);

	}

	public function disable() : void {

		\add_action( 'after_setup_theme', [ $this, 'action_disable_wp_emojicons' ] );
	}

	public function action_disable_wp_emojicons() {

		// all actions related to emojis
		\remove_action( 'admin_print_styles', 'print_emoji_styles' );
		\remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		\remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		\remove_action( 'wp_print_styles', 'print_emoji_styles' );
		\remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		\remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		\remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// filter to remove TinyMCE emojis
		\add_filter( 'tiny_mce_plugins', [ $this, 'filter_disable_emojicons_tinymce' ] );
	}

	// We will need the following filter function to disable TinyMCE emojicons:
	public function filter_disable_emojicons_tinymce( array $plugins ) : array {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, [ 'wpemoji' ] );
		} else {
			return [];
		}
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
