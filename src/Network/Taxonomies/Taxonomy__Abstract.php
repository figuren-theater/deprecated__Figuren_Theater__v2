<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\SiteParts as SiteParts;


/**
 * This is the ruleset for new taxonomies.
 * They must extend this class and therefore 
 * implement all connected interfaces.
 */
abstract class Taxonomy__Abstract implements SiteParts\SitePart, Taxonomy__CanInitEarly__Interface
{
	const NAME = '';

	public $post_types = [];
	public $labels;
	public $args;

	/**
	 * Flag to toggle some post_type parameters
	 * based on user capability
	 */
	protected $visibility_flag = false;

	protected $menu_icon_charcode = false;


	// function __construct() {}


	// LATE replacement for a constructor
	public function init_taxonomy() : void
	{
		$this->prepare_tax();
		$this->prepare_post_types();
		$this->prepare_labels();
		$this->prepare_args();
	}

	protected function prepare_tax() : void {}

	protected function prepare_post_types() : array
	{
		return $this->post_types;
	}

	protected function prepare_labels() : array
	{
		return $this->labels;
	}

	protected function prepare_args() : array
	{
		// fallback for old 'TAX_Extended' taxonomies
		if (
			method_exists( $this, 'register_taxonomy__default_args')
			&&
			method_exists( $this, 'register_extended_taxonomy__args')
		) {
			$this->args = array_merge(
				$this->register_taxonomy__default_args(),
				$this->register_extended_taxonomy__args(),
			);
		}
		return $this->args;
	}


	// By default of CPTsExtended, there are no Icons on Taxonomies
	// listed on the 'At a Glance' Widget.
	// Let's change this!
	public function show_icon_at_a_glance() : void {

		// because we are hooked onto 'admin_head-index.php'
		// this can be loaded allmost anywhere.
		// Don't do this.
		// Just load on the dashboard
		$_current_screen = get_current_screen();

		if ( ! isset( $_current_screen->base ) || 'dashboard' !== $_current_screen->base )
			return;

		if ( !is_string($this->menu_icon_charcode) || 4 != strlen($this->menu_icon_charcode))
			return;


		echo '<style>
			#dashboard_right_now a.taxo-'.$this::NAME.'-count:before,
			#dashboard_right_now span.taxo-'.$this::NAME.'-count:before {
				content: "\\'.$this->menu_icon_charcode.'";
			}
		</style>';
	}

}
