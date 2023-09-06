<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Themes;
use Figuren_Theater\SiteParts;

use Figuren_Theater\inc\EventManager;


/**
 * Fundament of all SitePartManager classes.
 * SiteParts (in our situation) are
 * all the elements of our WordPress Site,
 * that we maybe want to change in certain situations.
 *
 * Theese SiteParts will be especially
 *  -- Plugins
 *  -- Options
 *  -- Taxonomies
 *  -- Post_Types
 *  -- RewriteRules
 *  -- UserRoles
 *  -- etc. ... (will be continued)
 */
class Themes_Manager implements EventManager\SubscriberInterface {

	const PARENTTHEMES = \WP_CONTENT_DIR . '/parent-themes';
	const CHILDTHEMES  = \WP_CONTENT_DIR . '/themes';

	public function __construct() {

		// use two theme folders
		// do not hook to 'plugins_loaded' as it is too late
		\register_theme_directory( self::PARENTTHEMES );
		\register_theme_directory( self::CHILDTHEMES );

		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $this );
	}

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * Load Order Admin: 
	 * init -> widgets_init -> wp_loaded -> admin_menu -> admin_init
	 * 
	 * That's why we take 'admin_menu', it's the 1st relevant action to hook on, 
	 * for admin-only stuff, without triggering during request on admin-ajax.php,
	 * which is used from the frontend also
	 * 
	 * So, Load everything 
	 * from the collection into prepared properties,
	 * so that our prepared methods can act on this.
	 * 
	 * @return array
	 */
	public static function get_subscribed_events() : array {
		return [
			'plugins_loaded'    => 'init',
			'after_setup_theme' => 'network_wide_theme_supports',
			'wp_resource_hints' => [ 'remove_unused_resource_hints', 999, 2 ],
			

			// Remove version info from head and feeds for security reasons
			'the_generator'     => 'the_generator', 

			'wp_head'           => [ 'clean_html_head', 0 ],
			// 'admin_init'   => [ 'debug', 60 ],
		];
	}


	/**
	 * Init our Manager onto WordPress 
	 * 
	 * This could mean 'register_something', 
	 * 'add_filter_to_soemthing' or anything else,
	 * to do (probably on each SitePart inside the collection).
	 *
	 * This should be hooked into WP.
	 */
	public function init() : void {

		// MOVED INTO // ft-privacy
		// 
		// download and reference external styles locally
		// $ft_wptt_webfont_loader = new ft_WPTT_WebFont_Loader();
		// \Figuren_Theater\FT::site()->EventManager->add_subscriber( $ft_wptt_webfont_loader );


		// Make sure that new installed themes go into the right directory.
		\add_filter( 'upgrader_package_options', [ $this, 'upgrader_package_options' ] );
	}

	public function network_wide_theme_supports() {

		// ;) the only not theme-support, but needed anyway
		\add_post_type_support( 'page', 'excerpt' );

		// Add post formats (http://codex.wordpress.org/Post_Formats)
		\add_theme_support(
			'post-formats',
			[
				'aside',
				'audio',
				'chat',
				'gallery',
				'image',
				'link',
				'quote',
				'status',
				'video',
			]
		);

		// this should be core func
		\add_theme_support( 
			'post-thumbnails',
			\get_post_types_by_support( 'thumbnail' )
		);

		// THIS IS MIGHTY !!
		// https://make.wordpress.org/core/2021/07/01/block-styles-loading-enhancements-in-wordpress-5-8/

		// Really nice, but doesn't work that good with twentytwenty, 
		// so lets enable this on the next theme switch
		\add_filter( 'should_load_separate_core_block_assets', '__return_true' );

	}


	/*
	 *  Removes <link rel="prefetch" for WP assets not used in the theme
	 *  https://wordpress.stackexchange.com/a/307766
	 *
	 * @see "Resource Hints - What is Preload, Prefetch, and Preconnect?"
	 *      https://www.keycdn.com/blog/resource-hints
	 *  
	 * Filters domains and URLs for resource hints of relation type.
	 *
	 * @since 4.6.0
	 * @since 4.7.0 The `$urls` parameter accepts arrays of specific HTML attributes
	 *              as its child elements.
	 *
	 * @param array  $urls {
	 *     Array of resources and their attributes, or URLs to print for resource hints.
	 *
	 *     @type array|string ...$0 {
	 *         Array of resource attributes, or a URL string.
	 *
	 *         @type string $href        URL to include in resource hints. Required.
	 *         @type string $as          How the browser should treat the resource
	 *                                   (`script`, `style`, `image`, `document`, etc).
	 *         @type string $crossorigin Indicates the CORS policy of the specified resource.
	 *         @type float  $pr          Expected probability that the resource hint will be used.
	 *         @type string $type        Type of the resource (`text/html`, `text/css`, etc).
	 *     }
	 * }
	 * @param string $relation_type The relation type the URLs are printed for,
	 *                              e.g. 'preconnect' or 'prerender'.
	 */
	public function remove_unused_resource_hints( $hints, $relation_type) {

		if ('dns-prefetch' === $relation_type)
			// used URLs in the theme
			return \wp_dependencies_unique_hosts();
	
		/*if ('preload' === $relation_type)
			return [
				[
					'href' => 'https://fonts.gstatic.com',
					'crossorigin' => '*',
				]
			];*/
	
		return $hints;
		

		// $prefetch_urls = \wp_dependencies_unique_hosts();
		// die(var_export($prefetch_urls,true));


		// TODO
		// LATER when we have a clear /uploads domain/subdomain
		
		// $custom_urls = array();
	
		// Add media directory, makes sense only if it's a CDN or sep. subdomain
		// $upload_dir = wp_get_upload_dir();
		// $custom_urls[] = $upload_dir['baseurl'];
	
		// 
		// $prefetch_urls = array_merge($prefetch_urls,$custom_urls);


		// return array_diff(wp_dependencies_unique_hosts(), $hints);


		// return $prefetch_urls;
	}

	/**
	 * Clean wp_head output from unused default stuff
	 * 
	 * see also !!!!
	 * https://wordpress.stackexchange.com/questions/211467/remove-json-api-links-in-header-html
	 * 
	 * @package project_name
	 * @version 2022.06.29
	 * @author  Carsten Bach
	 */
	public function clean_html_head() {

		// Display the link to the Really Simple Discovery service endpoint, EditURI link
		\remove_action( 'wp_head', 'rsd_link' );

		// Display the link to the Windows Live Writer manifest file.
		\remove_action( 'wp_head', 'wlwmanifest_link' );
		
		// index link
		// remove_action( 'wp_head' , 'index_rel_link' );

		// remove both /wp-json endpoints 
		// with rel='https://api.w.org/' 
		// and  rel="alternate" type="application/json" 
		\remove_action( 'wp_head', 'rest_output_link_wp_head' );

		// 
		// remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

		// To remove the rest_output_link_header
		// remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	}


	/**
	 * Remove version info from head 
	 * and feeds for security reasons
	 *
	 * normally you would use:
	 * add_filter( 'the_generator', '__return_null' );
	 *
	 * @package project_name
	 * @version version
	 * @author  Carsten Bach
	 *
	 * @param   string       $wp_claim [description]
	 * @return  [type]                 [description]
	 */
	public function the_generator( string $wp_claim ) : string
	{
		return sprintf(
			'<meta name="generator" content="%1$s" />',
			'websites.fuer.figuren.theater ' . FT_PLATTFORM_VERSION
		);
	}



	/**
	 * Make sure that new installed themes go into the right directory.
	 *
	 * Themes installed via wp-admin/network/theme-install.php should in all cases
	 * go into content/parent-themes
	 */
	public function upgrader_package_options( array $options ) : array
	{
		if ( !isset($options['hook_extra']['action']) || 'install' !== $options['hook_extra']['action'])
			return $options;
	
		if ( !isset($options['hook_extra']['type']) || 'theme' !== $options['hook_extra']['type'])
			return $options;
	
		if ( \WP_CONTENT_DIR . '/themes' === $options['destination'])
			$options['destination'] = \WP_CONTENT_DIR . '/parent-themes';
		
		return $options;
	}


	public function debug(){
	
		// \do_action( 'qm/debug', $this->collection );

		global $wp_theme_directories;
		\do_action( 'qm/debug', $wp_theme_directories );
		\do_action( 'qm/debug', \wp_get_themes( ) );
		\do_action( 'qm/debug', \get_site_transient( 'theme_roots' ) );
		\do_action( 'qm/debug', \search_theme_directories() ); 
		// do_action( 'qm/debug', \search_theme_directories( true ) ); // !!! this was needed to update the list of avail. themes on wp-admin/network/themes.php
		// do_action( 'qm/debug', \get_site_transient( 'theme_roots' ) );
		
		// do_action( 'qm/debug', \wp_get_themes( array( 'allowed' => true ) ) );
		// do_action( 'qm/debug', \WP_Theme::get_allowed_on_network() );
		
		
	}


}





\add_action( 
	'Figuren_Theater\init', 
	function ( $ft_site ) : void {

		if ( ! is_a( $ft_site, 'Figuren_Theater\ProxiedSite' ))
			return;

		// 4. Setup SitePart Manager for 'Themes'
		$ft_site->set_Themes_Manager( new Themes_Manager() );
	},
	50
);

