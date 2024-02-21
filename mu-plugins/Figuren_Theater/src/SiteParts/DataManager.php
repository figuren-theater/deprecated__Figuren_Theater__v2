<?php
declare(strict_types=1);

namespace Figuren_Theater\SiteParts;

use Figuren_Theater\inc\EventManager;


/**
 * PartHelper for handling 'Taxonomies' and custom 'Post_Types'
 * 
 * The word 'data' in this class and its properties & methods maps 
 * to 'taxonomies' and 'post_types' in general WP.
 *
 * Everything related to thoose data-types is handled by the DataManager of our Site.
 * Its main job is registrering thoose data-types in WP, 
 * followed by preparing the types for registration 
 * and keeping its prepared state inside the internal collection for later modification.
 */
abstract class DataManager extends SitePartsManagerAbstract {

	protected $registerer;
	protected $TitletagManager;

	function __construct( $registerer, SitePartsCollectionInterface $collection ) {

		$this->collection = $collection;
		$this->registerer = $registerer;

		// Prepare
		$this->TitletagManager = TitletagManager::init();

		add_action( 'init', [ $this, 'prepare_data_type' ], 0 );
		add_action( 'init', [ $this, 'prepare_data_seo' ], 1 );
		// SPECIAL ORDER:
		// we need the PT 'ft_site' registered with TAX 'hm-utility'
		// the TAX is loaded on 'init' default (no int prio)
		// but when we run on 'init' default, we are too early
		// to get this connection ready. 
		// 
		// We need to use the 'hm_utility_init' action, which 
		// "fires after the hm-utility taxonomy has been registered."
		// 
		// OR 
		// 
		// use 'init' 11, as long as there is no native way (within our namespace)
		// to attach one of our post_types to a special action.
		add_action( 'init', [ $this, 'init' ], 11 ); // 11 is NEEDED 

	}


	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array {
		return [];
	}

	/**
	 * This is slightly different for post_types and taxonomies,
	 * that's why it is an abstract method.
	 *
	 * It's hooked early onto 'init' 0
	 * 
	 * @return void 
	 */
	abstract public function prepare_data_type() : void;


	// runs on 'init' 1
	public function prepare_data_seo() {

		// Run on every element of our collection
		array_map(
			// do not set any Interace on this $collection_el
			// because it could be 'Taxonomy__CanInitEarly__Interface' 
			// and '\WP_Taxonomy' also
			function( $collection_el ) {
				
				if ( $collection_el instanceof Data__CanAddYoastTitles__Interface ) {
					$this->TitletagManager->add_data( $collection_el );
				}

				if ( $collection_el instanceof Data__CanAddYoastVariables__Interface ) {
					$this->TitletagManager->add_variables( $collection_el );
				}
			},
			array_values( $this->collection->get() )
		);
	}


	// runs on 'init' 11
	public function init() : void {
		// Run on every element of our collection
		array_map(
			// do not set any Interace on this $collection_el
			// because it could be 'Taxonomy__CanInitEarly__Interface' 
			// and '\WP_Taxonomy' also
			function( $collection_el ) {
				// Runs our RegistrationHandler
				$this->register( $collection_el );

				// Add subscribed events of post_types and taxonomies to EventManager
				if ( $collection_el instanceof EventManager\SubscriberInterface) {
					\Figuren_Theater\FT::site()->EventManager->add_subscriber( $collection_el );
				}
			},
			// Runs on all elements,
			// but not its names (,the array keys)
			array_values( $this->collection->get() )
		);
	}


	abstract public function register( $collection_el ) : bool;
}
