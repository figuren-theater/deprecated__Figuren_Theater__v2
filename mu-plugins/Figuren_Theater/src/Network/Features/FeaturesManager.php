<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\FeaturesRepo;
use Figuren_Theater\SiteParts;
use Figuren_Theater\Network\Taxonomies;



class FeaturesManager extends SiteParts\SitePartsManagerAbstract {

	/**
	 * Collection of all our Features
	 *
	 * ... is an array of 'Feature' or 'UtilityFeature' objects.
	 * 
	 * @var SitePartCollection
	 */
	public $collection;

	/**
	 * List with term-slugs of all features, that websites WANTS to load
	 * 
	 * @var array
	 */
	protected $current_site_features = [];

	/**
	 * List with term-slugs of features, that will be (or were already) enabled.
	 * 
	 * @var array
	 */
	protected $enabled_features = [];

	/**
	 * List with term-slugs of features, that will be (or were already) disabled.
	 * 
	 * @var array
	 */
	protected $disabled_features = [];


	/**
	 * The taxonomies, which represent our Features in the DB
	 * 
	 * @var array
	 */
	protected $taxonomies = [];


	function __construct( SiteParts\SitePartsCollectionInterface $collection ) {
		$this->collection = $collection;

		$this->taxonomies = [
			Taxonomies\Taxonomy__ft_feature_shadow::NAME,
		];
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $this );
	}

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 * 
	 * 'setup_theme' is the most early action to hook on
	 * when we want to trick register_tayxonomy()
	 * DAMN, THIS IS MUCH TOOO LATE !!!
	 * 
	 * with a cutom wpdb statement we can use 
	 * 'muplugins_loaded' or even earlier 'Figuren_Theater\loaded'
	 * AND
	 * we already know our 'ft_site'->ID     YEAH!!!
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array {
		return [
			'Figuren_Theater\loaded' => [ 'init', 11 ],
	
			'init'                   => [ 'enable_utilityFeatures', 0],
			// now the current user is set
			// so we know at least, if the user_is_logged_in()
			// 'admin_init' => ['enable_adminFeatures', 0 ], // needed to have the UtilityFeaturesManager available for REST calls & co.
			'network_admin_menu'     => [ 'enable_adminFeatures', 0 ],
			'user_admin_menu'        => [ 'enable_adminFeatures', 0 ],
			'admin_menu'             => [ 'enable_adminFeatures', 0 ],

		];
	}


	protected function load_dependencies() : void {
		// TODO // maybe should load 'hm-utility'
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
		//-1 Features are added as callables to the collection
		// 0. Load collection to the 'hm-utility' features array via add_filter
		// 1. get terms of 'hm-utility' from current site // NOPE, not anymore
		// 2. get terms of 'ft_feature_shadow' from current site
		// 3. merge terms into one array_of_features
		// 4. reduce FeaturesCollection to this new array_of_features
		// 4.1. by using Feature->disable();
		$this->disableFeatures();

		// 5. Run Feature->enable() on every left element inside FeaturesCollection
		//    a little earlier than post_types and taxonomies are registered normally
		//    to that Features can change some of their properties 
		//    prior registration.
		$this->enableFeatures( 'enable' );
	}


	/**
	 * Runs the disabling routine 
	 * of each feature this website doesn't want
	 */
	public function disableFeatures() : void {
		// Run ..
		array_map(
			// takes a term-slug from our indexed list
			// one-by-one
			function( string $slug ) {
				// Runs the disabling routine 
				// of the feature itself
				$this->collection->get( $slug )->disable();
			},
			// Runs on all disabled elements,
			$this->get_disabled_features()
		);
	}

	/**
	 * Runs the enabling routine 
	 * of each feature this website does want
	 *
	 * @todo   cleanup: cloned to PluginsManager !!
	 */
	public function enableFeatures( $method = 'enable' ) : void { 

		// Run ..
		array_map(
			// takes a term-slug one-by-one 
			// from our indexed list
			// of features to enable
			function( string $slug ) use ( $method ) {
				$_feature = $this->collection->get( $slug );

				// Runs the enabling routine 
				// of the feature itself
				$_feature->$method();

				// add hooks, only on the first enabling
				// not again, during 'enable__on_admin'
				if ('enable' === $method) {

					// Add subscriptions to Eventmanager
					if ( $_feature instanceof EventManager\SubscriberInterface )
						\Figuren_Theater\FT::site()->EventManager->add_subscriber( $_feature );
				}
			},
			// Runs on all enabled elements,
			$this->get_enabled_features()
		);
	}


	/**
	 * Runs the admin-side enabling routine 
	 * of each feature this website does want
	 *
	 * @todo   cleanup: cloned to PluginsManager !!
	 */
	public function enable_adminFeatures() {

		if ( ! \is_user_logged_in() && ! \is_admin() )
			return;
		$this->enableFeatures( 'enable__on_admin' );
	}



	public function enable_utilityFeatures() {

		if ( ! \is_user_logged_in() && ! \is_admin() )
			return;

		// 1. Setup all UtilityFeatures as part of our Collection
		// 1.1. Create Collection 
		$collection = UtilityFeaturesCollection::get_collection();

		// 1.2. Add all UtilityFeatures to the collection
		$repo = [
			[
				'Figuren_Theater\\UtilityFeaturesRepo' => WPMU_PLUGIN_DIR . '/Figuren_Theater/src/Network/Post_Types/UtilityFeaturesRepo',
			],
		];

		// This is done from inside /Figuren_Theater/Features NOPE
		// We have to call from here to make our autoloader aware of the classes NOPE
		// We use an Semi-Autoloader on the FeaturesRepo Folder
		new Bootstrap_FeaturesRepo( $repo, 'UtilityFEAT' );

		// Load UtilityFeaturesManager
		$manager = new UtilityFeaturesManager( $collection );
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $manager );
		\Figuren_Theater\FT::site()->set_UtilityFeaturesManager( $manager );
	}


	/**
	 * Collect and persist all features
	 * this website wants to load.
	 * 
	 * Get 'ft_feature_shadow' taxonomy terms and
	 * 'hm-utility' taxonomy terms for the 'ft_site' post,
	 * which represents our current website,
	 * and save them together as indexed list of term-slugs.
	 * 
	 * @return      Array      indexed list with slugs of Features this website wants to load
	 */
	private function get_current_site_features() : array {
		// minimal caching
		if ( ! empty( $this->current_site_features ) )
			return $this->current_site_features;

		// Init our WP_Query wrapper
		$ft_query = \Figuren_Theater\FT_Query::init();
		$blog_id = \get_current_blog_id();

		$_FeaturesManager = $this;
		// $_FeaturesManager = \Figuren_Theater\FT::site()->FeaturesManager;
		// $_FeaturesManager = new static;
		// $_FeaturesManager = __CLASS__;

		$_current_site_features = $ft_query->use_cache( 
			"{$blog_id}__get_cached_current_site_features", 
			'Figuren_Theater', 
			[
				$_FeaturesManager,
				'get_cached_current_site_features'
			]
		);

		// if it is no Error,
		// but an empty or filled array,
		// keep it
		if ( is_array( $_current_site_features ) )
			$this->current_site_features = $_current_site_features;

		// and bye bye
		return $this->current_site_features;
	}
	public function get_cached_current_site_features() : array {

		// get our current site-post object
		$_post_id = \Figuren_Theater\FT::site()->get_site_post_id();

		// make sure it works
		if ( ! is_int( $_post_id ) || 0 === $_post_id )
			return [];

		// Take the taxonomy-slug(s) and the Post-ID and get all features 
		// as a list with term-IDs as keys and term-slugs as values.
		// 
		// Like here:
		// [ 376 => 'feature-slug-with-nice-name']
		// 
		// 1. Init $wpdb wrapper class
		$_wpdb = \Figuren_Theater\FT_wpdb::init();
		// 2. 
		// $_current_site_features = $_wpdb->get_terms_by_tax_and_post_id(
		// use this 2x times faster version
		return $_wpdb->get_term_slugs_by_tax_and_post_id(
			$this->taxonomies,
			$_post_id
		);
	}


	/**
	 * Get the similarities between 
	 * the Features of the Repo
	 * and the Features, this website wants to load.
	 *
	 * So we can only load a feature, 
	 * if there is a corresponding file in our repo.
	 * Find the matches.
	 *
	 * Because array_filter() preserves keys, 
	 * you should consider the resulting array to be 
	 * an associative array even if the original array 
	 * had integer keys for there may be holes in your sequence of keys.
	 *
	 * ARRAY_FILTER_USE_KEY - pass key as the only argument to callback instead of the value
	 * 
	 * @see https://www.php.net/manual/en/function.array-filter.php
	 * 
	 * @return Array      indexed list with slugs of Features to enable
	 */
	public function get_enabled_features() : array {
		// minimal caching
		if ( ! empty( $this->enabled_features ) )
			return $this->enabled_features;

		$_current_site_features = array_flip( $this->get_current_site_features() );

		return $this->enabled_features = array_keys( 
			array_filter(
				$this->collection->get(),
				function( $slug ) use ( $_current_site_features ) {
					return isset( $_current_site_features[ $slug ] );
				},
				ARRAY_FILTER_USE_KEY
			)
		);
	}


	/**
	 * Get the difference between 
	 * the Features of our collection
	 * and the Features to enable,
	 * so we know what to disable.
	 * 
	 * @return Array      indexed list with slugs of Features to disable
	 */
	public function get_disabled_features() : array {
		// minimal caching
		if ( ! empty( $this->disabled_features ) )
			return $this->disabled_features;

		// compare Features from the Repo to the ones
		// this site needs
		return $this->disabled_features = array_diff(
			array_keys( $this->collection->get() ),
			$this->get_enabled_features()
		);
	}

}




\add_action( 
	'Figuren_Theater\init', 
	function ( $ft_site ) : void {

		if ( ! is_a( $ft_site, 'Figuren_Theater\ProxiedSite' ))
			return;

		// 1. Setup all Features as part of our Collection
		// 1.1. Create Collection
		// It's important, to do that before ADDing post_types,
		// to properly instantiate our collection.
		$collection = FeaturesCollection::get_collection();

		// 1.2. Add all Features to the collection
		//      
		// This is done from inside /Figuren_Theater/Features NOPE
		// We have to call from here to make our autoloader aware of the classes NOPE
		// We use an Semi-Autoloader on the FeaturesRepo Folder
		new Bootstrap_FeaturesRepo;

		// 1.3. Setup SitePart Manager for 'Features'
		// with its personal RegistrationHandler and our 
		// prepared Collection
		$ft_site->set_FeaturesManager( 
			// with its FeaturesCollection
			new FeaturesManager( $collection )
		);
	},
	20
);
