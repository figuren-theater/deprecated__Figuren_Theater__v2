<?php
declare(strict_types=1);

namespace Figuren_Theater\SiteParts;


/**
 * Contract for all collections of SiteParts
 * this is 3/4 CRUD elements. 
 * 
 * The last missing one - 'update',
 * comes from the collection manager class itself.
 */
interface SitePartsCollectionInterface
{
	public function add( String $name, $input = null ) : bool;
	public function get( String $name = '' );
	public function remove( String $name ) : bool;
}