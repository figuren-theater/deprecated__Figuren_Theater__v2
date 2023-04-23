<?php
declare(strict_types=1);

namespace Figuren_Theater;
use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\SiteParts;

use Figuren_Theater\inc\EventManager as Inc;






/**
 * The Site Class represents a subsite of our WP network 
 * it contains data from a typical WP_Site object and  WP_Site meta, 
 * the corresponding and network-wide synched 'ft_site' post and its terms.
 * 
 * This is the most central class in this whole setup.
 * It's the big boss of our company.
 *
 * It gets all relevant parts of this website,
 * the SitePartsManagers, as properties. They do all the heavy work 
 *
 * This Site is proxied through FT::site(), 
 * which allows all PartManagers to talk to the same boss
 * and also bring in their parts of work.
 */
class ProxiedSite
{
	/**
	 * 'blog_id' of our site
	 * @var int
	 */
	public $blog_id = null;

	/**
	 * ID of the corresponding and network-wide synched 'ft_site' post representing this Site 
	 * @var int
	 */
	protected $post_id = 0;

	/**
	 * Instance of 'ft_site' post
	 * @var WP_Post
	 */
	protected $post = false;

	/**
	 * Keep all essential SiteParts
	 * in there respective properties.
	 * 
	 * @var subClass of SitePartsManagerInterface
	public $TaxonomiesManager;
	public $Post_TypesManager;
	public $FeaturesManager;
	public $UtilityFeaturesManager;
	public $Options_Manager;
	public $Admin_UIManager;
	public $PluginsManager;
	public $EventManager;

	DO WE REALLY NEED TO PREPARE THEESE PROPS ??
	 */


	/**
	 * "Construct a Site" huh? ;)
	 *
	 * So, go on and load everything our Site needs
	 * 
	 * @param    Int    $blog_id of the Site you want to load, 
	 *                  defaults to the currents site blog_id.
	 */
	function __construct( int $id = 0 )
	{
		$this->blog_id = $this->get_site_id( $id );

	}


	/**
	 * To make this 'ProxiedSite' class callable from everywhere,
	 * we needed to remove all dependencies from the construstor,
	 * which is and was a little bit ugly and maybe revoked in the future.
	 *
	 * For now, this is the method, 
	 * where all important PartManagers join our new big company.
	 * 
	 */

	public function set_TaxonomiesManager( SiteParts\SitePartsManagerInterface $TaxonomiesManager )
	{
		$this->TaxonomiesManager = $TaxonomiesManager;
	}

	public function set_Post_TypesManager( SiteParts\SitePartsManagerInterface $Post_TypesManager )
	{
		$this->Post_TypesManager = $Post_TypesManager;
	}

	public function set_FeaturesManager( SiteParts\SitePartsManagerInterface $FeaturesManager )
	{
		$this->FeaturesManager = $FeaturesManager;
	}

	public function set_UtilityFeaturesManager( SiteParts\SitePartsManagerInterface $UtilityFeaturesManager )
	{
		$this->UtilityFeaturesManager = $UtilityFeaturesManager;
	}

	public function set_Options_Manager( SiteParts\SitePartsManagerInterface $Options_Manager )
	{
		$this->Options_Manager = $Options_Manager;
	}

	public function set_Admin_UIManager( SiteParts\SitePartsManagerInterface $Admin_UIManager )
	{
		$this->Admin_UIManager = $Admin_UIManager;
	}

	public function set_PluginsManager( SiteParts\SitePartsManagerInterface $PluginsManager )
	{
		$this->PluginsManager = $PluginsManager;
	}

	public function set_EventManager( Inc\EventManager $EventManager )
	{
		$this->EventManager = $EventManager;
	}

	public function set_Themes_Manager( $Themes_Manager )
	{
		$this->Themes_Manager = $Themes_Manager;
	}


	/**
	 * Get blog_id of current site or
	 * setup new site object for foreign site.
	 * 
	 * @param  int    $id  blog_id
	 * 
	 * @return int         blog_id
	 */
	public function get_site_id( int $id ) : int {
		/*
		WHY should it be that complicated?

		if ( 0 === $id )
		{
			if ( null === $this->blog_id )
			{
				$this->blog_id = get_current_blog_id();
			}
		}
		elseif ( is_int( $id ) && 0 < $id )
		{
			$this->blog_id = $id;
		}

		return $this->blog_id;
		*/
		return \get_current_blog_id();
	}


	/**
	 * Get the post object of the 'ft_site' post
	 * corresponding to this website.
	 *
	 * @todo  Remove fixed 'ft_site' post->ID
	 * 
	 * @return    WP_Post   'ft_site'-post
	public function get_site_post()
	{
		// minimal caching
		if (false === $this->post)
		{
			// Init our WP_Query wrapper
			$ft_query = FT_Query::init();

			// run our well prepared WP_Query
#			$_post = $ft_query->find_by_id( 484 );
			if ( 0 === $this->post_id ) {
#PSEUDOCODE		$_post = $ft_query->find_ft_site_by_slug( 484 ); // 484 'private site' on ROOT
				// persist results
#				$this->post_id = $_post->ID;
			} else {
#				$_post = $ft_query->find_ft_site_by_id( $this->post_id ); // 484 'private site' on ROOT
				// the cached version of the upper
				$_post = $ft_query->use_cache( 'ft_site', 'Figuren_Theater', [$ft_query,'find_first_ft_site'] );

			}

			// everything fine ?
			if ( ! $_post instanceof \WP_Post )
				return;

			// persist results
			$this->post = $_post;
		}

		return $this->post;
	}
	 */


	/**
	 * Get the post->ID of the 'ft_site' post
	 * corresponding to this website.
	 * 
	 * @return    int   'ft_site'-post->ID
	 */
	public function get_site_post_id() : int {
		// minimal caching
		if ( \ms_is_switched() || 0 === $this->post_id) {
			// get ID directly from the DB via $wpdb
			$this->post_id = \Figuren_Theater\FT_wpdb::init()->get_ft_site_post_id();
		}

		return $this->post_id;
	}



	/**
	 * Checks if current site has feature
	 *
	 * wrapper for is_object_in_term()
	 *
	 * Determine if the given object is associated with any of the given terms.
	 *
	 * The given terms are checked against the object's terms' term_ids, names and slugs.
	 * Terms given as integers will only be checked against the object's terms' term_ids.
	 * If no terms are given, determines if object is associated with any terms in the given taxonomy.
	 * 
	 * @param  int|string|array   $features     Optional. Term term_id, name, slug or array of said. Default null.
	 * @return boolean           [description]
	 */
	public function has_feature($features)
	{
		if ( ! $this->FeaturesManager || is_int($features) )
		{
			$in_ft_feature_shadow = \is_object_in_term( $this->get_site_post_id(), Taxonomies\Taxonomy__ft_feature_shadow::NAME, $features );
			// $in_hm_utility = \is_object_in_term( $this->get_site_post_id(), Features\UtilityFeaturesManager::TAX, $features );
			// return ( $in_ft_feature_shadow || $in_hm_utility );
			return ( $in_ft_feature_shadow );
		}

		if (is_string($features)) {
			$features = [ $features ];
		}

		$_ft = $this->FeaturesManager->get_enabled_features();

		return ! (bool) array_diff( $features, $_ft );
	}
}















/** deprecated  */
class OOOLdSite
{

	/**
	 * All site-levels this Site belongs to.
	 * Site-Levels should be a taxonomy on 'ft_site'
	 * @var array
	 */
	public $site_level = [];


	public function has_level($levels)
	{
		return is_object_in_term( $this->get_site_post_id(), Taxonomies\Taxonomy__ft_level_shadow::NAME, $levels );
	}



}





#add_action( 'init', __NAMESPACE__.'\\debug_ft_feature', 42);
#debug_ft_feature();


function debug_ft_feature(){
/*
$_wpdb = \Figuren_Theater\FT_wpdb::init();
// Init our WP_Query wrapper
$ft_query = FT_Query::init();

// run our well prepared WP_Query
  $start1 = microtime(true);
  // function code here
  $_wpdb->get_ft_site_post();
  $time_taken1 = microtime(true) - $start1;


// run our well prepared WP_Query
  $start2 = microtime(true);
  // function code here
  $_wpdb->get_ft_site_post_id();
  $time_taken2 = microtime(true) - $start2;


// run our well prepared WP_Query
  $start3 = microtime(true);
  // function code here
  $ft_query->find_first_ft_site();
  $time_taken3 = microtime(true) - $start3;

// run our well prepared WP_Query
  $start4 = microtime(true);
  // function code here
  $v4 = $ft_query->use_cache( 'find_first_ft_site', 'Figuren_Theater', [$ft_query,'find_first_ft_site'] );
  $time_taken4 = microtime(true) - $start4;

*/

/*	wp_die(
		'<pre>'.
		var_export(
			array(
				__FILE__,
				// do not rely on any domain-logic 
				// to prevent errors with custom domain names
#				parse_url( $_SERVER['HTTP_HOST'] ),
				// go for 
				// typicall results looked like this 
				// 						// time spent on functions (
#				$time_taken1,			//  0 => 0.0003788471221923828,
#				$time_taken2,			//  1 => 0.00019407272338867188,
#				$time_taken3,			//  2 => 0.000885009765625,
#				$time_taken4,			//  
#				$v4,
#				FT::site()->FeaturesManager,
			),
			true
		).
		'</pre>'
	);*/
#	\do_action( 'qm/debug','here we are');
#	\do_action( 'qm/debug', FT::site()->has_feature('ohne-schlagworte') );
#	\do_action( 'qm/debug', FT::site()->has_feature('managed-core-options') );
#	\do_action( 'qm/debug', FT::site()->has_feature(['managed-core-options','ohne-schlagworte']) );
}
