<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\Network\Features;
use Figuren_Theater\Options;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Sync;

use Figuren_Theater\Onboarding\Impressum;
use Figuren_Theater\Theming\WP_Better_Emails;

use function WP_Better_Emails\get_default_from;
use function WP_Better_Emails\get_default_from_email;

/**
 * 
 */
class Feature__core__is_core_site extends Features\Feature__Abstract {

	const SLUG = 'core-is-core-site';

	function __construct() {
		$this->screen_ids = [
			// 'options-general',
			// 'options-writing',
			'options-reading',
			// 'options-discussion',
			// 'options-media',
			// 'options-permalink',
			// 'options-privacy',
			'themes',
			'settings_page_wpbe_options',
			'settings_page_impressum',
			// 'seo_page_wpseo_social',
		];

		$this->non_default_options = [

			##########################################
			# READING /wp-admin/options-reading.php
			##########################################
			'show_on_front' => 'page',

			##########################################
			# HIDDEN or DEPRECATED defaults
			##########################################
#			'template' => new Sync\SyncFrom(),
#			'stylesheet' => new Sync\SyncFrom(),


			##########################################
			# 'WP Better Emails' Plugin Options
			##########################################
			// DISABLED here
			// This is set during enable() 
			// by prepare__wpbe_options()
			// 'wpbe_options' => new Sync\SyncFrom(), 



			##########################################
			# 'Yoast SEO' (only 'Social') Plugin Options
			##########################################
			// DISABLED, better use the defaults
			// 'wpseo_social' => new Sync\SyncFrom(), 
		];

		$this->non_default_siteoptions = [

			'upload_space_check_disabled' => 1,
		];


		$this->non_managed_default_options = [

			###################################################################
			# not managed by 'UtilityFeature__managed_core_options', by default
			###################################################################
#			'current_theme' => new Sync\SyncFrom(),

			##########################################
			# 'Impressum' Plugin Options
			##########################################
	##NW		'impressum_imprint_options' => [], // new Sync\SyncFrom(),

		];
	}




	public function enable() : void 
	{

		// 
		$this->prepare__wpbe_options();

		// Wrapper for some blocks and 
		// kinda default plugins
		// 
		// New way w/o PluginsManager
		// ugly, but working (FOR NOW)
		require WP_PLUGIN_DIR . '/ft_coresites/ft_coresites.php';

		\add_filter( 
			'Figuren_Theater.config', 
			function ( array $config ) : array {
				// used on heute.theater
				$config['modules']['site_editing']['block-visibility']       = true;
				$config['modules']['site_editing']['markdown-comment-block'] = true;
				// used for 404 pages
				$config['modules']['site_editing']['dinosaur-game']          = true;
				// 
				$config['modules']['site_editing']['embed-block-for-github'] = false; // not yet production ready
				// used in blog posts, everywhere ...
				$config['modules']['onboarding']['ft-core-block-domaincheck'] = true;
				
				return $config;
			}
		);


		// after the plugin added its option
		\add_action( 'Figuren_Theater\loaded', [$this,'modify_options'], 12 ); 

		// because we do want to interact with a Post_Type__CanBeAutoDistributed__Interface, 
		// we need to hook on init between 0 and 10
		\add_action( 'init', [$this, 'register_auto_distributing'], 5 ); 




		\add_filter( 'Figuren_Theater\Routes\rewrite_bases', [$this, 'rewrite_bases'] );



	}

	/**
	 * Change author-rewite-base to 'crew'.
	 *
	 * @package [package]
	 * @since   2.11
	 *
	 * @param  array $rewrites List of custom rewite bases.
	 *           
	 * @return array           Modified list of custom rewite bases.
	 */
	public function rewrite_bases( array $rewrites ) : array {
		
		$rewrites['author_base'] = 'crew';
		return $rewrites;
	}

	public function modify_options() {

		// new Options\Factory( 
		// 	$this->non_managed_default_options
		// );
		
		// A synced option is 97% OK, but
		// for the ['page'] id, which is unique for each site
		// this is an anoying BUG
		// 
		// new Options\Option_Synced(
			// Impressum\OPTION,
			// [],
			// Impressum\BASENAME
		// );
		$ft_impressum_options = \get_blog_option( 1, Impressum\OPTION );
		unset($ft_impressum_options['page']);
		
		new Options\Option_Merged(
			Impressum\OPTION,
			$ft_impressum_options,
			Impressum\BASENAME
		);
		
		$New_Core_SiteOptions = new Options\Factory( 
			$this->non_default_siteoptions, 
			'Figuren_Theater\Options\Option', 
			'core', 
			'site_option'
		);

		array_map( 
			function( $option_name, $option ) {
				$handled_option = \Figuren_Theater\API::get('Options')->get( "option_{$option_name}" );
				if ( null !== $handled_option )
					$handled_option->set_value( $option );
			},
			array_keys( $this->non_default_options ),
			$this->non_default_options
		);
	}

	public function register_auto_distributing() : void 
	{
		// Add re-usable blocks to the distributable post_types
		new Sync\AllowDistribution( 'wp_block' );
		
		// Add Links to the distributable post_types
		new Sync\AllowDistribution( Post_Types\Post_Type__ft_link::NAME );
	}

	protected function prepare__wpbe_options() {
		// get HTML email template from file
		// because its a little too big for normal wp_options
		$_ft_wpbe_template = \file_get_contents( __DIR__ . '/../../assets/html/figuren_theater_coresites__wpbe__email.tmpl.html' );

		$this->non_default_options['wpbe_options'] = [
			'from_email'         => WP_Better_Emails\get_default_from_email(),
			'from_name'          => WP_Better_Emails\get_default_from(),
			'template'           => $_ft_wpbe_template,
			'plaintext_template' => "
%content%

---
%impressum%

%home_url%
",
		];
	}



	public function enable__on_admin() : void 
	{

		// $this->modify_adminui__on_admin();
	}
/*
	protected function modify_adminui__on_admin()
	{

		$Admin_UICollection = \Figuren_Theater\API::get('Admin_UI');

		$notice = new Admin_UI\Rule__will_add_admin_notice( 
			'manage_options', // user_capability
			$this->screen_ids, // screen_ID
			new Admin_UI\Admin_Notice(
				sprintf( 'Some Settings are managed for all core-sites by %s', '<em>'.__CLASS__.'</em>' ),
				'is-dismissible info'
			)
		);
		$Admin_UICollection->add( $this::SLUG.'__admin_notice', $notice);
	}
*/

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
