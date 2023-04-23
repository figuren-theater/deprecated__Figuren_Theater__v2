<?php
declare(strict_types=1);

namespace Figuren_Theater;


// Das ZIEL
// call from everywhere
// ( Using a Static Proxy: )
// 
// Example from https://www.alainschlesser.com/singletons-shared-instances/
// $result = Services::get( 'Database' )->query( $query );
// 
// PSEUDOCODE
// $result = Site::api( 'SitePart' )->add|update|remove( ...$args );




// Static Proxy.
final class API
{

	public static function get_service_locator(): ServiceLocatorContainer
	{
		static $service_locator = null;

		if ( null === $service_locator )
		{
			// You can have arbitrary logic in here to decide what
			// implementation to use.
			$service_locator = new ServiceLocator();
		}

		return $service_locator;
	}

	public static function add( string $key, callable $service )
	{
		// Forward call to actual implementation.
		return self::get_service_locator()->add( $key, $service );
	}

	public static function get( string $key )
	{
		// Forward call to actual implementation.
		return self::get_service_locator()->get( $key );
	}
}
