<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;


/**
 * Features can be enabeled per site 
 * and work like capabilities for users.
 * 
 * They are independently from (yet, non-existing) 
 * blog-groups aka site-levels, which (should) work like user-roles
 *
 */
abstract class Feature__Abstract implements Feature__Interface
{

	/**
	 * We need for sure a slug
	 * because we are acting with real 
	 * taxonomy terms in the DB
	 */
	const SLUG = '';

	public function get_slug() : string
	{
		return $this::SLUG;
	}

	// don't make this an abstract
	// to keep the possibility
	// to create UtilityFeatures
	// with just a SLUG
	public function enable() : void {}
	public function enable__on_admin() : void {}

	public function disable() : void
	{
		// \Figuren_Theater\API::get('FEAT')->remove( $this->get_slug() ); // is this really needed
	}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it 
// as long as this file lives in the \FeaturesRepo ;)
