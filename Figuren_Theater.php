<?php # -*- coding: utf-8 -*-
/*
* Plugin Name:  figuren.theater NETWORK | Overhead collection
* Description:  Collection of needed overhead for the whole figuren.theater network
* Plugin URI:	https://figuren.theater
* Version:      2022.09.05
* Author:       Carsten Bach
* Author URI:   https://carsten-bach.de
* License:      MIT
*/

declare(strict_types=1);

namespace Figuren_Theater;


\defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/Psr4AutoloaderClass.php';
// instantiate the loader
$loader = new \Figuren_Theater\Psr4AutoloaderClass();
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$loader->addNamespace( __NAMESPACE__, dirname( __FILE__ ) . '/src', true );
// $loader->addNamespace( __NAMESPACE__, dirname( __FILE__ ) . '/tests', true );


// this is best-practice
add_action( 'init', function() {
} );
// but only this works
\load_muplugin_textdomain( 'figurentheater', '/Figuren_Theater/languages' );




require dirname( __FILE__ ) . '/src/API.php';
require dirname( __FILE__ ) . '/src/inc/EventManager/EventManager.php';
// require dirname( __FILE__ ) . '/src/Network/Setup/Site_Registration.php'; // moved into Figuren_Theater\Onboarding
require dirname( __FILE__ ) . '/src/Network/Features/FeaturesManager.php';
// require dirname( __FILE__ ) . '/src/Network/Plugins/PluginsManager.php'; // removed completely, 2023-02-06

require WPMU_PLUGIN_DIR . '/FT/ft-options/plugin.php';


require dirname( __FILE__ ) . '/src/Network/Themes/Themes_Manager.php';
require dirname( __FILE__ ) . '/src/Network/Taxonomies/TaxonomiesManager.php';
require dirname( __FILE__ ) . '/src/Network/Post_Types/Post_TypesManager.php';
require dirname( __FILE__ ) . '/src/Network/Admin_UI/Admin_UIManager.php';



// moved into must-use-ft.php to run after autoloaded.modules
// \do_action( __NAMESPACE__ . '\\init', FT::site() );
// \do_action( __NAMESPACE__ . '\\loaded', FT::site() );







add_action( 'init', __NAMESPACE__.'\\debug_ft__show_ui_on_TAX_and_PT', 9); // must be between 0 and 10
function debug_ft__show_ui_on_TAX_and_PT() {

	if (
		'local' !== WP_ENVIRONMENT_TYPE
		&&
		! \current_user_can( 'manage_sites' )
	)
		return;



	// DEBUG
	// API::get('TAX')->update( 'ft_az_index', 'args', ['show_ui'=> true, 'show_admin_column'=> true, 'show_in_menu'=> true, ] ); // WORKING  !!!!
	// API::get('TAX')->update( 'ft_production_shadow', 'args', ['show_ui'=> true, 'show_admin_column'=> true, 'show_in_menu'=> true, ] ); // WORKING  !!!!
	API::get('TAX')->update( 'ft_feature_shadow', 'args', ['show_ui'=> true] ); // WORKING  !!!!
	// API::get('TAX')->update( 'hm-utility', 'args', ['show_ui'=> true, 'show_in_menu'=> true] ); // WORKING  !!!!
	// API::get('TAX')->update( 'ft_site_shadow', 'args', ['show_ui'=> true] ); // WORKING  !!!!
	API::get('PT')->update( 'ft_site', 'args', ['show_ui'=> true, 'show_in_menu'=> true] ); // WORKING  !!!!
	// DEBUG


	// \do_action( 'qm/debug', [$_SERVER['SERVER_PROTOCOL'],$_SERVER] );
}












// die();
// die( ft_debug_print_o( FT::site() ) );

// 
// TEST GrumPHP with some fake errors //
// 
/*
error _log(var_export([ 
	$include,
	$file['path'],
	$file['root'],
	$normalized_full_path,
	$normalized_htdocs_path
],true));
*/
// 
// TEST GrumPHP with some fake errors //
// 


/*
// wp_die('we are here');
	?>
	<script>
		console .log(<?php echo json_encode(array($plugin,$actions,$required_plugins,$wds_required_plugin_network_activate)); ?>);
	</script>
	<?php*/



	// https://querymonitor.com/docs/logging-variables/

	// \do_action( 'qm/debug', 'This happened!' );
/*
\do_action( 'qm/debug', 'file_exists(): {file_exists}', [
    'file_exists' => file_exists($file),
] );
*/

// error_reporting(E_ALL);
// ini_set("display_errors", 1);
