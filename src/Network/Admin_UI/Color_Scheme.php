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



		// // figuren.theater4
		// \wp_admin_css_color( 
		// 	$this::NAME, 
		// 	__( 'figuren.theater', 'figurentheater' ),
		// 	$theme_dir . '/ft_admin-scheme4.css',
		// 	[ '#0f0f0f', '#f1f1f1', '#3858e9', '#d20394' ],
		// 	#array(
		// 	#	'base'    => '#6d024d',
		// 	#	'focus'   => '#fff',
		// 	#	'current' => '#fff',
		// 	#)
		// );


	  /**
	   * DISABLED: 4 beeing totally not ready !!
	   *
	   * USER_WEB styles  
	  \wp_admin_css_color( $this::NAME.'_current_theme', \esc_html( \get_bloginfo( 'name' ) ),
		$theme_dir . '/ft_admin-scheme4.css',
		$this->ft_prepare_admin_theme_styles(),
		// array(
			// 'base'    => '#6d024d',
			// 'focus'   => '#fff',
			// 'current' => '#fff',
		// )
	  );
	   */
	 


	}


	/**
	 * Function to get all the color settings resulting of merging core, theme, and user data.
	 *
	 * @since 
	 *
	 * @return array The colors to retrieve indexed by their slug.
	 */
	public static function get_all_colors() : array {
		$_global_settings = \wp_get_global_settings( [ 'color', 'palette' ] );
		if ( ! isset( $_global_settings['theme'] ) )
			return [];

		return \wp_list_pluck( 
			$_global_settings['theme'],
			'color',
			'slug'
		);
	}

  /**
   * DISABLED: 4 beeing totally not ready !!
   * 
   * [ft_prepare_admin_theme_styles description]
   *
   * @subpackage [subpackage]
   * @version    2022-10-20
   * @author Carsten Bach
   *
   * @return     [type]       [description]
   
  public function ft_prepare_admin_theme_styles() { 
	$ft_global_styles =  $this->ft_use_global_styles_to_login();

	$ft_background = '#f1f1f1';
	$ft_primary    = '#808080'; // grey
	$ft_text       = '#000';
	$ft_secondary  = '#404040'; // grey

	// $ft_background = get_theme_mod( 'header_footer_background_color' );
	$ft_background = ( isset( $ft_global_styles['background'] ) ) ? $ft_global_styles['foreground'] : $ft_background;
	// $ft_primary     = get_theme_mod( 'accent_accessible_colors' );
	$ft_primary     = ( isset( $ft_global_styles['primary'] ) ) ? $ft_global_styles['primary'] : $ft_primary;
	$ft_secondary     = ( isset( $ft_global_styles['secondary'] ) ) ? $ft_global_styles['secondary'] : $ft_secondary; // SHIT!! // THAT is why we need a own f.t ThemeProcessor API
	// $ft_secondary  = $ft_global_styles['secondary']; // SHIT!! // THAT is why we need a own f.t ThemeProcessor API
	$ft_text       = ( isset( $ft_global_styles['foreground'] ) ) ? $ft_global_styles['background'] : $ft_text; // SHIT!! // THAT is why we need a own f.t ThemeProcessor API


	return [
	  $ft_text,
	  $ft_background,
	  $ft_secondary,
	  $ft_primary
	];
  }
   */



}

