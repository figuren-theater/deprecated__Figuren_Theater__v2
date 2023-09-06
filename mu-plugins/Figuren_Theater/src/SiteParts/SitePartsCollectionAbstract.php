<?php
declare(strict_types=1);

namespace Figuren_Theater\SiteParts;



// Static Proxy.
abstract class SitePartsCollectionAbstract {

#	const PROXIED_CLASS = 'SitePartsCollectionInterface';

#	protected static $Proxied_class = 'SitePartsCollectionInterface';

	abstract public static function get_collection() : SitePartsCollectionInterface;
/*	public static function get_collection() : SitePartsCollectionInterface
	{
		//Kept for easier abstraction

		static $collection = null;

		if ( null === $collection ) {
			// You can have arbitrary logic in here to decide what
			// implementation to use.
#			$collection = new ProxiedSitePartsCollectionAbstract();
#			$collection = new self :: $Proxied_class;
			$PROXIED_CLASS = self::PROXIED_CLASS;
			$collection = new $PROXIED_CLASS;
		}

		return $collection;
	}*/

	public static function get( String $name = '' )
	{
		// Forward call to actual implementation.
		self::get_collection()->get( $name );
	}


	public static function add( String $name, $input = null )
	{
		// Forward call to actual implementation.
		self::get_collection()->add( $name, $input );
	}


	public static function remove( String $name )
	{
		// Forward call to actual implementation.
		self::get_collection()->remove( $name );
	}


	public static function update( String $name, String $property, $new_value )
	{
		// Forward call to actual implementation.
		self::get_collection()->update( $name, $property, $new_value );
	}
}


