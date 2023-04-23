<?php
declare(strict_types=1);

namespace Figuren_Theater\SiteParts;



/**
 * Collection of SiteParts 
 */
abstract class ProxiedSitePartsCollectionAbstract implements SitePartsCollectionInterface
{
	/**
	 * Collection of all our SiteParts
	 * 
	 * @var array
	 */
	public $elements = [];


	public function has( string $name ): bool {
		// return array_key_exists( $name, $this->elements );
		return isset( $this->elements[ $name ] );
	}

	/**
	 * Add SitePart to collection
	 * 
	 * @param String $name  Key of our future-collection element 
	 *                      and usually the name of the SitePart.
	 * @param mixed  $input Data we want so save 
	 * 
	 * @return bool         Was 'Adding to collection' successfull ?
	 */
	public function add( String $name, $input = null ) : bool
	{
		if (!$this->validate( $input ))
			return false;

		$this->elements[ $name ] = $input;
		return true;
	}


	/**
	 * Get all or one SitePart from collection
	 * 
	 * @param  string $name [description]
	 * 
	 * @return Object|Array of Objects|anything ...
	 */
	public function get( string $name = '' ) {
		if ( empty( $name ) ) {
			return $this->elements;
		}

		if ( $this->has( $name ) ) {
			return $this->elements[ $name ];
		}
	}


	/**
	 * Remove SitePart from collection
	 * 
	 * @return bool
	 */
	public function remove( String $name ) : bool
	{
		if ( $this->has( $name ) ) {
			unset($this->elements[ $name ]);
			return true;
		}
		return false;
	}


	/**
	 * Checks wether it is allowed to add something to our collection.
	 * This should typically check for the implementation of needed interfaces,
	 * not do any checking on its values.
	 * 
	 * @param  mixed   $input    Could be anything,
	 *                           but are typically our SiteParts.
	 *
	 * @return bool
	 */
	abstract protected function validate( $input ) : bool;


	public function update( String $name, String $property, $new_value ) : bool
	{
		$el = $this->get( $name );

		if ( !$el )
			return false;

		if ( ! $this->validate( $el ) && ! property_exists( $el, $property ) )
			return false;

		$el->$property = \wp_parse_args( $new_value, $el->$property );
		return true;
	}
}

