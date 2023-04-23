<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;

use Figuren_Theater\inc\EventManager;
/**
Plugin Name: Page Template Plugin : 'Good To Be Bad'
Plugin URI: http://www.wpexplorer.com/wordpress-page-templates-plugin/
Version: 1.1.0
Author: WPExplorer
Author URI: http://www.wpexplorer.com/
*/

/**
 * Heavily based on thees q&a threads
 * @see  https://wordpress.stackexchange.com/questions/3396/create-custom-page-templates-with-plugins
 * @see  https://wordpress.stackexchange.com/questions/17385/custom-post-type-templates-from-plugin-folder
 * 
 * and the there mentioned Plugin and corresponding tutorial
 * @see  https://github.com/wpexplorer/page-templater
 * @see  
 */
class Post_TypesTemplateLoader implements EventManager\SubscriberInterface {

	/**
	 * A reference to an instance of this class.
	 */
	// private static $instance;

	/**
	 * The array of templates that this plugin tracks.
	 * 
	 * @var array
	 */
	protected $templates = [];

	/**
	 * [$folder_abspath description]
	 * 
	 * @var string
	 */
	protected $folder_abspath = '';

	/**
	 * Post Type to load template for
	 * 
	 * @var string
	 */
	protected $post_type = '';


	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	// private function __construct( array $templates, string $folder_abspath = '' )
	function __construct( array $templates, string $folder_abspath, string $post_type )
	{

		// Add your templates to this array.
		# $this->templates = array(
		# 	'goodtobebad-template.php' => 'It\'s Good to Be Bad',
		# );
		$this->templates = $templates;
		
		// define the base folder to look at
		$this->folder_abspath = $folder_abspath;
		
		// 
		$this->post_type = $post_type;

	}



	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(

			// FRONTEND & ADMIN

			// Add a filter to the template metabox
			'theme_templates'     => ['theme_templates', 10, 4],
	
			// Add a filter to the template include to determine if the page has our
			// template assigned and return it's path
			'template_include'    => 'template_include',
	

			// ADMIN ONLY

			// Add a filter to the save post to inject out template into the page cache
			'wp_insert_post_data' => ['wp_insert_post_data', 10, 3],

			// 
			// 'init' => ['debug', 1000 ],
		);
	}



	/**
	 * Adds our template to the template dropdown for v4.7+
	 * 
	 * Filters list of templates for a theme.
	 * The dynamic portion of the hook name, `$post_type`, refers to the post type.
	 *
	 * Possible hook names include:
	 *
	 *  - `theme_post_templates`
	 *  - `theme_page_templates`
	 *  - `theme_attachment_templates`
	 *
	 * @package project_name
	 * @version 2022.04.11
	 * @author  Carsten Bach
	 *
	 * @see     https://developer.wordpress.org/reference/hooks/theme_templates/
	 * 
	 * @since   WP 3.9.0
	 * @since   WP 4.4.0 Converted to allow complete control over the `$page_templates` array.
	 * @since   WP 4.7.0 Added the `$post_type` parameter.
	 *
	 * @param   string[]     $templates      Array of template header names keyed by the template file name.
	 * @param   WP_Theme     $theme          The theme object.
	 * @param   WP_Post|null $post           The post being edited, provided for context, or null.
	 * @param   string       $post_type      Post type to get the templates for.
	 *
	 * @return  Array                        Array of template header names keyed by the template file name.
	 */
	public function theme_templates( array $templates, \WP_Theme $theme, \WP_Post|null $post, string $post_type ) : array
	{
		// guard clausing
		if ( $this->post_type !== $post_type )
			return $templates;

		// glue defaults with ours
		return array_merge( $templates, $this->templates );
	}



	/**
	 * Checks if the template is assigned to the page
	 *
	 * This filter hook is executed immediately 
	 * before WordPress includes the predetermined template file. 
	 * 
	 * This can be used to override WordPress’s default template behavior.
	 *
	 * @see     https://developer.wordpress.org/reference/hooks/template_include/
	 *
	 * @package project_name
	 * @version 2022.04.11
	 * @author  Carsten Bach
	 *
	 * @param   string       $template The path of the template to include.
	 * 
	 * @return  string                 The path of the template to include.
	 */
	public function template_include( string $template ) : string
	{
		// Get global post
		global $post;
		
		// Guard clausing 
		// Return the search template if we're searching (instead of the template for the first result)
		if ( \is_search() )
			return $template;

		// Guard clausing 
		// Return template if post is empty
		if ( ! is_a( $post, 'WP_Post' ) )
			return $template;

		// Guard clausing 
		// Return template if wrong post_type
		if ( $this->post_type !== $post->post_type )
			return $template;

		// Guard clausing 
		// if a template is found in theme 
		// or child theme directories
		// return this instead
		// $file = 'tvzu.php';
		$parts = explode("/", $template );
		$file = array_pop( $parts );
		if ( $template === \locate_template( array( $file ) ) )
			return $template;


		// get saved template from DB
		$_current_template = \get_post_meta( $post->ID, '_wp_page_template', true );

		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[ $_current_template ] ) )
			return $template;

		// Allows filtering of file path
		/**
		 * [$folder_abspath description]
		 * @var [type]
		 */
		$folder_abspath = \apply_filters( 
			__NAMESPACE__ . '\\template_include\folder_abspath', 
			$this->folder_abspath, 
			$template, 
			$this, 
			$_current_template
		);

		// glue together
		$file =  $folder_abspath . $_current_template;

		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} 

		// Return template
		return $template;

	}



	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 * 
	 * Filters slashed post data just before it is inserted into the database.
	 *
	 * @see     https://developer.wordpress.org/reference/hooks/wp_insert_post_data/
	 * 
	 * @package project_name
	 * @version 2022.04.11
	 * @author  Carsten Bach
	 *
	 * @since   WP 2.7.0
	 * @since   WP 5.4.1 `$unsanitized_postarr` argument added.
	 *
	 * @param   array $data                An array of slashed, sanitized, and processed post data.
	 * @param   array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param   array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 *
	 * @return  array                      An array of slashed, sanitized, and processed post data.
	 */
	public function wp_insert_post_data( array $data, array $postarr, array $unsanitized_postarr ) : array
	{

		// just a shortcut
		$_pt = $postarr['post_type'];

		// guard clausing 
		// Return data if wrong post_type
		if ( $this->post_type !== $_pt )
			return $data;

		// Create the key used for the themes cache
		$cache_key = $_pt.'_templates-' . md5( \get_theme_root() . '/' . \get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = \wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}

		// New cache, therefore remove the old one
		\wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		\wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $data;
	}



	/**
	 * Returns an instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

}
