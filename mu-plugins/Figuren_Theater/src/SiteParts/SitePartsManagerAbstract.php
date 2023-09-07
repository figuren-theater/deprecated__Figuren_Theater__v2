<?php
declare(strict_types=1);

namespace Figuren_Theater\SiteParts;

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
abstract class SitePartsManagerAbstract implements SitePartsManagerInterface, EventManager\SubscriberInterface {

	/**
	 * Collection of all our SiteParts
	 * 
	 * @var SitePartsCollectionInterface
	 */
	public $collection;

	public function __construct( SitePartsCollectionInterface $collection ) {
		$this->collection = $collection;

		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $this );

	}

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	abstract public static function get_subscribed_events() : array;

	/**
	 * Init our Manager onto WordPress 
	 * 
	 * This could mean 'register_something', 
	 * 'add_filter_to_soemthing' or anything else,
	 * to do (probably on each SitePart inside the collection).
	 *
	 * This should be hooked into WP.
	 */
	abstract public function init() : void;
}
