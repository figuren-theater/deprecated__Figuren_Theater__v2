<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;
use Figuren_Theater\SiteParts as SiteParts;


/**
 * This is the ruleset for new taxonomies.
 * They must extend this class and therefore 
 * implement all connected interfaces.
 */
abstract class Post_Type__Abstract implements SiteParts\SitePart, Post_Type__CanInitEarly__Interface
{
	const NAME = '';

	public $labels;
	public $args;

	/**
	 * Flag to toggle some post_type parameters
	 * based on user capability
	 */
	protected $visibility_flag = false;

	// LATE replacement for a constructor
	public function init_post_type() : void
	{
		$this->prepare_pt();
		$this->prepare_labels();
		$this->prepare_args();
	}

	protected function prepare_pt() : void {}


	protected function prepare_labels() : array
	{
		return $this->labels;
	}

	protected function prepare_args() : array
	{
		// fallback for old 'TAX_Extended' taxonomies
		if (
			method_exists( $this, 'register_post_type__default_args')
			&&
			method_exists( $this, 'register_extended_post_type__args')
		) {
			$this->args = array_merge(
				$this->register_post_type__default_args(),
				$this->register_extended_post_type__args(),
			);
		}
		return $this->args;
	}

	abstract public static function get_instance();

}
