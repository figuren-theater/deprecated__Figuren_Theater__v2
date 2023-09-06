<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\Network\Features;
use Figuren_Theater\Options;


/**
 * do not manage core-options at a Figuren_Theater\core_site
 * or is package #4 fur fully flexible websites 
 *
 * ???
 */
class Feature__decisions_not_options extends Features\Feature__Abstract implements EventManager\SubscriberInterface {
	
	// was formerly called 'managed-core-options'
	const SLUG = 'decisions-not-options';


	/**
	 * Options, that are handled for that Feature.
	 * This prop only mirrors the options for the different methods of this class.
	 * 
	 * @var Array
	 */
	protected $core_options = [];
	protected $core_ms_options = [];

	protected $screen_ids = [];

	protected $blog_upload_space = 50;

	function __construct()
	{
		$this->screen_ids = [
			'options-general',
			'options-writing',
			'options-reading',
			'options-discussion',
			'options-media',
			'options-permalink',
			// 'options-privacy'
			'themes',
			'site-settings-network',
			'settings-network',
		];

		$this->core_options = [
			// ///////////////////////////////////////
			// GENERAL /wp-admin/options-general.php
			// ///////////////////////////////////////

				// 'blogname' => 'My Blog',
				// 'blogdescription' => sprintf(__('Just another %s weblog'), $current_site->site_name ),

				// 'gmt_offset' => '-1',
				'timezone_string' => 'Europe/Berlin',
				'date_format' => 'd. F Y',
				'time_format' => 'H:i',
				'start_of_week' => 1,

				'links_updated_date_format' => 'd. F Y H:i', // hidden // related to links


			// ///////////////////////////////////////
			// WRITING /wp-admin/options-writing.php
			// ///////////////////////////////////////

				'default_category' => 1,
				'default_post_format' => 0,
				// 'default_link_category' => 2,
				// 'default_link_category' => 122,
				// 'default_link_category' => 0, // ????? why was this set, and why to 0


			// ///////////////////////////////////////
			// READING /wp-admin/options-reading.php
			// ///////////////////////////////////////

				'show_on_front' => 'page',
				// 'page_on_front' => '', // ID is created and set during Site_Setup
		// TODO				'page_for_posts' => '', // TODO add correct IDs of pages created during setup
				'posts_per_page' => 5,
				'posts_per_rss' => 10,
				'rss_use_excerpt' => 1,
				'blog_public' => 1, // discourage search engines from indexing with '0'

			// ///////////////////////////////////////
			// DISCUSSION /wp-admin/options-discussion.php
			// ///////////////////////////////////////

			// Default post settings
				'default_pingback_flag'=>1,
				'default_ping_status'=>'open',
				'default_comment_status'=>'open',

			// Other comment settings
				'require_name_email' => 1,
				'comment_registration' => 0,
				'close_comments_for_old_posts' => 0,
				'close_comments_days_old' => 14,
				'show_comments_cookies_opt_in'=>1,
				'thread_comments' => 1,
				'thread_comments_depth' => 3,
				'page_comments' => 0,
				'comments_per_page' => 50,
				'default_comments_page' => 'newest',
				'comment_order' => 'asc',

			// Email me whenever
				'comments_notify'=>1,
				'moderation_notify'=>1,

			// Before a comment appears
				'comment_moderation'=>0,
				'comment_previously_approved'=>1,

			// Comment Moderation
				'comment_max_links'=>2,
				// 'moderation_keys'=> new Sync\SyncFrom(),

			// Disallowed Comment Keys
				// 'disallowed_keys'=> new Sync\SyncFrom(),

			// Avatars
				'show_avatars'=> 0,
				'avatar_rating'=>'G',
				'avatar_default' => 'mystery',


			// ///////////////////////////////////////
			// MEDIA /wp-admin/options-media.php
			// ///////////////////////////////////////

			'thumbnail_size_w'    => 150,
			'thumbnail_size_h'    => 150,
			'thumbnail_crop'      => 1,
			'medium_size_w'       => 400,
			'medium_size_h'       => 400,
			'medium_crop'         => 0,
			'large_size_w'        => 1024,
			'large_size_h'        => 1024,
			'large_crop'          => 0,
			'medium_large_size_w' => 768,
			'medium_large_size_h' => 0,
			'medium_large_crop'   => 1,

			// ///////////////////////////////////////
			// PERMALINKS /wp-admin/options-permalink.php
			// ///////////////////////////////////////

			// 'permalink_structure' => '/%year%/%postname%/',
			'permalink_structure' => '/%category%/%year%/%monthnum%/%postname%/', // following: https://trello.com/c/mt6IgMLx/325-url-architektur-zu-live-migrieren
			'tag_base' => '!!',
			'category_base' => '.',

			// ///////////////////////////////////////
			// PRIVACY /wp-admin/options-privacy.php
			// ///////////////////////////////////////

			// 'page_for_privacy_policy' => , // ID is created and set during Site_Setup


			// ///////////////////////////////////////
			// HIDDEN defaults
			// ///////////////////////////////////////


				'https_migration_required' => 0,

				'users_can_register' => 0,

				'use_balanceTags' => 1,
				'use_smilies' => 0,

				'hack_file' => 0, // _deprecated_file( 'my-hacks.php', '1.5.0' );
				'blog_charset' => 'UTF-8',

				'ping_sites' => '',
				'default_email_category' => 1,

				// 'template' => 'twentytwenty', // DO NOT HANDLE, leave to site-owners or maybe to ft_level(s)
				// 'stylesheet' => 'twentytwenty', // DO NOT HANDLE, leave to site-owners or maybe to ft_level(s)
				'html_type' => 'text/html',

				'use_trackback' => 0,
				'default_role' => 'subscriber',

				'uploads_use_yearmonth_folders' => 1,
				'upload_path' => '', # NO NEED TO HANDLE, but setting it to empty val, saves 2 DB requests, this is done by the 'filter__upload_dir' filter inside of Figuren_Theater\Routes\Virtual_Uploads
				'upload_url_path' => '', # NO NEED TO HANDLE, but setting it to empty val, saves 2 DB requests, this is done by the 'filter__upload_dir' filter inside of Figuren_Theater\Routes\Virtual_Uploads
				'blog_upload_space' => $this->blog_upload_space,

				'image_default_link_type' => 'file',
				'image_default_size' => 'medium', // @see https://wptavern.com/gutenberg-10-3-supports-default-image-sizes-continues-normalizing-toolbars-and-categorizes-theme-blocks
				'image_default_align' => '',

				'link_manager_enabled' => 0, // could easily 0, but at the moment (01/2021) feedwordpress uses it; 02/2021: kicked feedwordpress

				// 'fresh_site' => 0, // DO NOT HANDLE, because it's used to allow demo content (1) and to stop nagging the user with intros (2)

				/**
				 * By default all auto-update related stuff is handled very well by
				 * the constant AUTOMATIC_UPDATER_DISABLED in wp-config.default.php, 
				 * 
				 * but since 5.6 some core/plugin instances are asking 
				 * for specific update functionality directly, 
				 * so we give them an answer, here, again.
				 * 
				 * @see  https://developer.wordpress.org/reference/functions/core_auto_updates_settings/ Display WordPress auto-updates settings.
				 * @see https://wordpress.org/support/article/configuring-automatic-background-updates/ Configuring Automatic Background Updates
				 */
				'auto_update_core_dev'   => 0,
				'auto_update_core_minor' => 0,
				'auto_update_core_major' => 0,

				'auto_update_plugins' => 0,
				'auto_plugin_theme_update_emails' => [],

				// 'WPLANG' => '',// DO NOT HANDLE, this is set by Figuren_Theater\Onboarding\Impressum onImpressumChange ;)

				// '_wp_suggested_policy_text_has_changed' => 0, // DO NOT HANDLE // this would trigger an update_option() onto this
				// '_wp_suggested_policy_text_has_changed' => 'not-changed', // DO NOT HANDLE // this would trigger an update_option() onto this

				// 'template_root' => \ABSPATH . 'wp-content/themes', // this can be wp/wp-content/themes AND content/themes; depending on the CHILD theme
				// 'template_root' => \WP_CONTENT_DIR . '/themes', // this can be wp/wp-content/themes AND content/themes; depending on the CHILD theme
				'template_root' => \WP_CONTENT_DIR . '/parent-themes', // this can be wp/wp-content/themes AND content/themes; depending on the CHILD theme
			# DISABLED to test non-child-theming with 'wei'
			#		'stylesheet_root' => \WP_CONTENT_DIR . '/themes', // this can be made static, when we have at least one real own default theme and no site is looking for themes inside wp/wp-content/themes
		];

		$this->core_Option_Synceds = [
			// Comment Moderation
			'moderation_keys' => '', // new Sync\SyncFrom()

			// Disallowed Comment Keys
			'disallowed_keys' => '', // new Sync\SyncFrom()
		];

		$this->core_ms_options = [
			'auto_update_plugins' => 0,
			'auto_update_core_major' => 0,
			// 'auto_core_update_failed' => '', // DO NOT HANDLE, because it stores critical data, that might be useful on staging or dev environments
			// 'wpmu_upgrade_site' => '', // DO NOT HANDLE, because it stores the global int $wp_db_version, the WordPress database version at its mlast update.

			'registrationnotification' => 'no', // default: 'yes'
			'illegal_names' => [ 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator', 'files', 'assets', 'media', 'http', 'styleguide', 'theme', 'kein', 'mein', 'schoenstes', 'wirsinddas', 'cdu', 'fdp', 'gruene', 'afd', 'linke', 'spd', 'studium', 'cdn', 'fuer', 'demo', 'welttag' ],


			'upload_space_check_disabled' => 0, // Ensure upload_space_check_disabled=>1 when installing multisite  # https://github.com/wp-cli/wp-cli/pull/2238/commits/59d2ca9c76e579e4fe0a4c9387f610b889b61a32
			'blog_upload_space' => $this->blog_upload_space,
			'upload_filetypes' => 'jpg jpeg png gif mov avi mpg 3gp 3g2 midi mid pdf doc ppt odt pptx docx pps ppsx xls xlsx key mp3 ogg flac m4a wav mp4 m4v webm ogv flv svg xml',
			'fileupload_maxk' => 10240, // 10 MB per file

			'first_comment_author' => 'Frieda Theodor Bot',
			'first_comment_email' => 'info@figuren.theater',
			'first_comment_url' => 'https://figuren.theater/',
		];

		
		$this->core_ms_options['auto_core_update_failed'] = 0; // DO NOT HANDLE, because it stores critical data, that might be useful on staging or dev environments
		if ( 'production' === \WP_ENVIRONMENT_TYPE ) {
			$this->core_ms_options['dismissed_update_core'] = 0;
		}

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

		);
	}


	public function enable() : void
	{
		new Options\Factory( 
			$this->core_options
		);
		
		new Options\Factory( 
			$this->core_Option_Synceds,
			'Figuren_Theater\Options\Option_Synced',
			'core',
		);
		new Options\Factory( 
			$this->core_ms_options,
			'Figuren_Theater\Options\Option',
			'core',
			'site_option'
		);
		// Allow some Options to beeing autoloaded
		\Figuren_Theater\API::get( 'Options' )->get( 'option_blog_charset' )->db_strategy = 'autoload';

		// no need for plugins to register their
		// uninstall_hooks the default way
		// as everything is managed via composer
		// and a 
		// @TODO
		// global deprecation logic :(
		add_filter( 'pre_option_uninstall_plugins', '__return_zero' );
		add_filter( 'pre_update_option_uninstall_plugins', '__return_zero' );
	}



	public function enable__on_admin() : void {



		// \do_action( 'qm/debug', \Figuren_Theater\API::get( 'Options' )->get( 'option_blog_charset' ) );
		//
		$Admin_UICollection = \Figuren_Theater\API::get('Admin_UI');

		//
		//
		$notice = new Admin_UI\Rule__will_add_admin_notice( 
			'manage_options', // user_capability
			$this->screen_ids, // screen_ID[]
			new Admin_UI\Admin_Notice(
				sprintf( 'Settings are managed for the whole network by %s', '<em>'.__CLASS__.'</em>' ),
				'is-dismissible info'
			)
		);
		$Admin_UICollection->add( $this::SLUG.'__admin_notice', $notice);


		//
		//
		// $remove_menus = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['options-general.php'] );
		// $Admin_UICollection->add( $this::SLUG.'__remove_menus', $remove_menus);





		$remove_discussion = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['options-general.php'=>'options-discussion.php'] );
		$Admin_UICollection->add( $this::SLUG.'__remove_discussion', $remove_discussion);
		$remove_writing = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['options-general.php'=>'options-writing.php'] );
		$Admin_UICollection->add( $this::SLUG.'__remove_writing', $remove_writing);
		$remove_reading = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['options-general.php'=>'options-reading.php'] );
		$Admin_UICollection->add( $this::SLUG.'__remove_reading', $remove_reading);
		$remove_media = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['options-general.php'=>'options-media.php'] );
		$Admin_UICollection->add( $this::SLUG.'__remove_media', $remove_media);
		$remove_permalink = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['options-general.php'=>'options-permalink.php'] );
		$Admin_UICollection->add( $this::SLUG.'__remove_permalink', $remove_permalink);






		$remove_importers = new Admin_UI\Rule__will_remove_menus( 'manage_network_options', ['tools.php'=>'import.php'] );
		$Admin_UICollection->add( $this::SLUG.'__remove_importers', $remove_importers);
		

		// Hide "Category to tags converter" 
		// tool-box from tools.php
		\add_action( 'load-tools.php', function () {
			// Temporary remove the capability to 'import' from the current user,
			// to prevent the static 'tool_box' "Use the category-to-tags converter ..." from appearing.

			/**
			 * [user_has_cap filter]
			 *
			 * Filter on the current_user_can() function.
			 * This function is used to .....
			 *
			 * @param array $allcaps All the capabilities of the user
			 * @param array $cap     [0] Required capability
			 * @param array $args    [0] Requested capability
			 *                       [1] User ID
			 *                       [2] Associated object ID
			 */
			\add_filter( 'user_has_cap', function (  $allcaps, $cap, $args  ) {
				if ( isset( $allcaps['manage_sites'] ) )
					return $allcaps;

				unset( $allcaps['import'] );
				return $allcaps;
			}, 10, 3 );
		} );


		/**
		 * Remove help tab about the "Category-to-tags converter ..."
		 * @see  https://github.com/WordPress/WordPress/blob/8a90b8691ff7a7cf511ec624c912aacb6dd2b30a/wp-admin/tools.php#L45
		 */
		// \add_action( 'load-tools.php', function(){
		\add_action( 'admin_head-tools.php', function(){
			\get_current_screen()->remove_help_tab('converter');
		} );



		// Remove thoose settings that should be handled via direct input
		// aka all Setting handled via: SyncFrom()
		$_coresites = array_flip( FT_CORESITES );
		if( 
			// @TODO remove root here after migrated 2.12
			$_coresites['root'] === \get_current_blog_id()
			||
			$_coresites['mein'] === \get_current_blog_id()
		)
		{
			$reduced_core_options = array_filter($this->core_options, function($v) {
				return !is_object( $v );
			});
		} else {
			$reduced_core_options = $this->core_options;
		}

		// append ms settings to the settings to highlight
		$reduced_core_options = $reduced_core_options + $this->core_ms_options;

		// //
		// //
		$settings_highlight = new Admin_UI\Rule__will_highlight_settings(
			'manage_options', // user_capability
			$this->screen_ids, // screen_ID
			array_keys( $reduced_core_options )
		);
		$Admin_UICollection->add( $this::SLUG.'__highlight_settings', $settings_highlight );
	}

	public function disable() : void
	{
		// make sure update checks are disabled,
		// even when the site wants to handle all options on its own
		// 
		// by this we make sure,
		// that any file-changes are 
		// only applied through CI/CD on the bitbucket repository
		// 
		// and NOT by 'cowboy coding' via FTP or the UI
		$New_Core_Options = new Options\Factory( [
			'auto_update_core_dev'   => 0,
			'auto_update_core_minor' => 0,
			'auto_update_core_major' => 0,
		] );
	}



}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
