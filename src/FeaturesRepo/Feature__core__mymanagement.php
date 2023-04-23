<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Sync;
use Figuren_Theater\Network\Taxonomies;

use FT_CORESITES;

use WP_PLUGIN_DIR;


/**
 * Replacement for is_ft_core_site('mein')
 */
class Feature__core__mymanagement extends Features\Feature__Abstract
{
	const SLUG = 'core-mymanagement';

	public function enable() : void {

		// 
		\add_action( 'init', [$this, 'modify_Post_Type__ft_site'], 5); // must be between 0 and 10
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_feature_shadow'], 5); // must be between 0 and 10
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_level_shadow'], 5); // must be between 0 and 10

		// 'WEIRD'
		// we need to make sure even sites created directly from mein.f.t are distributed properly
		// and the easiest and mostly DRY way is ... by calling disable() within enable() ... F*CK.
		$this->disable();
	
		////////////////////////////////////////////////////////////////////////////
		// Stuff  for the special handling of quick-edit for mein.figuren.theater //
		////////////////////////////////////////////////////////////////////////////
		
		\add_filter( 
			'Figuren_Theater.config', 
			function ( array $config ) : array {
				$config['modules']['data']['distributor-remote-quickedit'] = true;
				return $config;
			}
		);

		// we need 'admin_init' because, we are using ajax
		\add_action( 'admin_init', function () {

			if ( ! \current_user_can( 'manage_sites' ) )
				return;

			\add_post_type_support( Post_Types\Post_Type__ft_site::NAME, 'distributor-remote-quickedit' );

			// 
			// require_once WP_PLUGIN_DIR . '/distributor-remote-quickedit/distributor-remote-quickedit.php';
			

		}, 0 );
	}


	public function modify_Post_Type__ft_site()
	{
		\Figuren_Theater\API::get('PT')->update( Post_Types\Post_Type__ft_site::NAME, 'args', [
			'show_ui' => true,
			'dashboard_activity' => true,
			'dashboard_glance' => true,
		] );
	}


	/**
	 * This taxonomy is loaded all over the network,
	 * the UI and also its automatic shadowing
	 * is only load on https://websites.fuer.figuren.theater 
	 * and https://mein.figuren.theater 
	 */
	public function modify_Taxonomy__ft_feature_shadow()
	{
		\add_action('admin_head-index.php', [ \Figuren_Theater\API::get('TAX')->get( Taxonomies\Taxonomy__ft_feature_shadow::NAME ), 'show_icon_at_a_glance' ] );

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_feature_shadow::NAME, 'args', [
			'show_ui' => true,
			'show_admin_column' => true,
			'dashboard_glance' => true,
		] );
	}


	/**
	 * This taxonomy is loaded all over the network,
	 * the UI and also its automatic shadowing
	 * is only load on https://websites.fuer.figuren.theater 
	 * and https://mein.figuren.theater 
	 */
	public function modify_Taxonomy__ft_level_shadow()
	{
		\add_action('admin_head-index.php', [ \Figuren_Theater\API::get('TAX')->get( Taxonomies\Taxonomy__ft_level_shadow::NAME ), 'show_icon_at_a_glance' ] );
		
		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_level_shadow::NAME, 'args', [
			'show_ui' => true,
			'show_admin_column' => true,
			'dashboard_glance' => true,
		] );
	}


	public function enable__on_admin() : void 
	{

		// register as 'distributable_post_types'
		// 
		// Yes, this is called from within 
		// Sync\AutoDistribute, so it should be there,
		// but for the very only one case, where
		// this is the site, which is distributed to,
		// and which uses a non-public post_type,
		// the normal func. won't reach the point to add this to the 
		// 'distributable_post_types' filter
		// 
		// so we have to ;)
		// 
		// this makes the UI on the receiving site work
		new Sync\AllowDistribution( Post_Types\Post_Type__ft_site::NAME );
	}


	public function disable() : void
	{
		// Moved this away from ft_site PT 
		// to keep things flexible 
		// using our created features_PT for such things
		// 
		// BUT this  works now on init|0
		$_coresites = array_flip( FT_CORESITES );
		new Sync\AutoDistribute( Post_Types\Post_Type__ft_site::NAME, $_coresites['mein'] ); // https://mein.figuren.theater

		// make sure all synced options come from the right network-site
		\add_filter( 
			'Figuren_Theater\Options\Option_Synced\remote_blog_id',
			function ( $blog_id ) : int {
				$_coresites = array_flip( FT_CORESITES );
				return $_coresites['mein'];
			}, 
			10, 
			1
		);
	}



}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
