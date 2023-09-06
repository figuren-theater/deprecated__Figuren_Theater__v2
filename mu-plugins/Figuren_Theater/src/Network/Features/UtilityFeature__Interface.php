<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;

interface UtilityFeature__Interface
{
	/**
	 * Humand read-able Title of the Feature
	 * 
	 * (following the naming conventions of 'hm-utility' by calling it 'label')
	 * @var string 
	 */
	public function get_label() : string;

	/**
	 * unique slug used as term_slug inside the 'hm-utility' TAX
	 * 
	 * (following the naming conventions of 'hm-utility' by calling it 'value')
	 * @var string
	 */
	public function get_value() : string;

	/**
	 * Will this Option (term) be selected by default for new posts of thar $post_type 
	 * 
	 * @var bool
	 */
	public function get_default() : bool;

	/**
	 * Posttype' this Feature is added to.
	 * 
	 * Because we are using taxonomies for Features, we can asign 
	 * terms to any post of post_types, that has the 'hm-utility' taxonomy registered.
	 * @var string
	 */
	public function get_post_type() : string;
}
