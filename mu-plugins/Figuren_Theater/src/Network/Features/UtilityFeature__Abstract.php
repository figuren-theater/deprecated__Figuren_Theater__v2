<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;



class UtilityFeature__Abstract implements UtilityFeature__Interface
// class UtilityFeature__Abstract extends Feature__Abstract implements UtilityFeature__Interface
{


	/**
	 * We need for sure a slug
	 * because we are acting with real 
	 * taxonomy terms in the DB
	 */
	const SLUG = '';

	/**
	 * Humand read-able Title of the Feature
	 * 
	 * (following the naming conventions of 'hm-utility' by calling it 'label')
	 * @var string 
	 */
	public $label;


	/**
	 * Will this Option (term) be selected by default for new posts of thar $post_type 
	 * 
	 * @var bool
	 */
	public $default = true;

	/**
	 * Posttype' this Feature is added to.
	 * 
	 * Because we are using taxonomies for Features, we can asign 
	 * terms to any post of post_types, that has the 'hm-utility' taxonomy registered.
	 * @var string
	 */
	public $post_type;

	/**
	 * Value = SLUG
	 *
	 * Needed a clone of SLUG as property,
	 * to filter array by this property,
	 * what is not possible with a constant.
	 * @var [type]
	 */
	public $value;

	public function __construct( string $title, bool $default = true, string $post_type = Post_Types\Post_Type__ft_site::NAME )
	{
		$this->label      = $title;
		$this->default    = $default;
		$this->post_type  = $post_type;

		$this->value = $this::SLUG;
	}

	public function get_label() : string
	{
		return $this->label;
	}
	public function get_value() : string
	{
		return $this->get_slug();
	}
	public function get_default() : bool
	{
		return $this->default;
	}
	public function get_post_type() : string
	{
		return $this->post_type;
	}


	public function get_slug() : string
	{
		return $this::SLUG;
	}
/*

	public function disable() : void
	{
		// we can do anything on 'disable',
		// but not removing the Feature from the collection,
		// because we need it there to properly
		// populate the UI created by the 'hm-utility' Plugin

		// \Figuren_Theater\API::get('FEAT')->remove( $this->get_slug() );
	}*/
}


























#add_action( 'init', __NAMESPACE__.'\\debug_ft_feature', 20);
#debug_ft_feature();
function debug_ft_feature(){


#$feature = new Feature('test feature','test1', false);


#	$_defaults = \wp_filter_object_list( $Taxonomie2->options, [ 'default' => true ], 'and', 'value' );
	// get all options recursively type casted as arrays
#	$_options = json_decode( json_encode($Taxonomie2->options) , true);

	wp_die(
		'<pre>'.
		var_export(
			array(
				apply_filters( 'hm_utility_options', [], 'ft_site' ),
#				$_defaults,
#				$_options,
			),
			true
		).
		'</pre>'
	);
}
