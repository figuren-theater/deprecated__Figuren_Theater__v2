<?php
declare(strict_types=1);

namespace Figuren_Theater;



// Container interface so that we can swap out the Service Locator
// implementation.
interface ServiceLocatorContainer {

	public function has( string $key ): bool;

	public function get( string $key );
}


// Basic implementation of a Service Locator.
// based on the very good !!! blog post at
// https://www.alainschlesser.com/singletons-shared-instances/
class ServiceLocator implements ServiceLocatorContainer {

	protected $services = [];

	public function has( string $key ): bool {
		return array_key_exists( $key, $this->services );
	}

	public function get( string $key ) {
		$service = $this->services[ $key ];
		if ( is_callable( $service ) ) {
			$service = $service();
		}

		return $service;
	}

	public function add( string $key, callable $service ) {
		$this->services[ $key ] = $service;
	}
}
