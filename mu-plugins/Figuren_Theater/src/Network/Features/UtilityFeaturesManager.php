<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;

use Figuren_Theater\SiteParts;


/**
 * Handler for 'hm-utility-taxonomy' mu-plugin
 *
 * Supported post_types:
 *   - ft_site 
 *   - ft_level
 *
 * Add new ones as needed for features!
 *
 * @see  /mu-plugins/hm-utility-taxonomy/README.md 
 */
class UtilityFeaturesManager extends SiteParts\SitePartsManagerAbstract
{

	const TAX = 'hm-utility';

	/**
	 * Collection of all registered UtilityFeatures of all post_types
	 *
	 * 
	 * @var array
	 */
	public $UtilityFeatures = [];

	/*
	function __construct()
	{
	
	 	\add_action( 'enqueue_block_editor_assets', function(){

			\add_filter( 'hm_utility_options', [ $this, 'load_UtilityFeatures_UI' ], 10, 2 );
		} );		
	}
		*/

	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			'hm_utility_options' => [ 'load_UtilityFeatures_UI', 11, 2 ],
			
			// Fires after the hm-utility taxonomy has been registered.
			'hm_utility_init' => 're_register_taxonomy_args',


			// 'admin_menu' => 'debug',
			// 'enqueue_block_editor_assets' => 'debug',
		);
	}

	
	// inherited from SitePartsManagerAbstract
	public function init() : void {}


	public function re_register_taxonomy_args() {
	    
		// \do_action( 'qm/debug', \get_taxonomy( static::TAX ) );
	    $args = (array) \get_taxonomy( static::TAX );
	    	    
		// @TODO
		// this works,
		// but we need a radio, not a tags-like text-field 
		// for this to be nice
	    // $args['show_in_quick_edit'] = true;


	    $args['publicly_queryable'] = true;
		    
		\register_taxonomy(
			static::TAX,
			[],
			$args
		);
		// \do_action( 'qm/debug', \get_taxonomy( static::TAX ) );
		// \do_action( 'qm/debug', \wp_get_object_terms( $post->ID, 'hm-utility' ) );
	}
	

	

	/**
	 * This gets hooked onto 'hm_utility_options' filter
	 * for every post_type registered with the TAX.
	 *
	 * It loads available features into the UI 
	 * of the typicall edit-screen for this PT by using the 
	 * 
	 * 'hm-utility-taxonomy' Plugin
	 *
	 * This method gets all UtilityFeatures for the PT the filter asks.
	 * If we have some, we send them all together to our transformation method
	 * 'get_UtilityFeatures_for_post_type' which returns everything nice,
	 * like the initialy calling filter wants it to be.
	 * An Array with a special syntax and important properties.
	 *
	 * @todo array_merge( INPUT $options + $this->get_UtilityFeatures() ) to enable UtilityFeatures 
	 *       to be created and added by UI only. At the moment we are just 'listening' for our files.
	 *
	 * @param array         $options              All options.
	 * @param string        $post_type            Post type name.
	 *
	 * @return array         $options              All options with our UtilityFeature(s) added.
	 */
	public function load_UtilityFeatures_UI ( array $options, String $post_type ) : array 
	{
		$options_of_pt = $this->get_UtilityFeatures_for_post_type( $post_type );
// die(var_export([$options,$post_type,$options_of_pt],true));

		if ( empty($options_of_pt) )
			return $options;

// die(var_export([$this->get_UtilityFeatures_for_hm_ui( $options_of_pt, $post_type ),$options,$post_type,$options_of_pt],true));
		// return $this->get_UtilityFeatures_for_hm_ui( $options_of_pt, $post_type );
		

		$options[] = $this->get_UtilityFeatures_for_hm_ui( $options_of_pt, $post_type );
		return $options;
	}


	/**
	 * Get, return and persist all UtilityFeatures
	 * from the 'big' list of all 'Features' - our FeatureCollection.
	 * 
	 * @return    Array      UtilityFeature[]
	 */
	public function get_UtilityFeatures() : array
	{
		// minimal caching
		if ( !empty( $this->UtilityFeatures) )
			return $this->UtilityFeatures;

		/*
		return $this->UtilityFeatures = array_values( array_filter(
			$this->collection->get(),
			function( $collection_el )
			{
				return ( $collection_el instanceof UtilityFeature__Interface );
			}
		) );
		*/
		return $this->UtilityFeatures = array_values( $this->collection->get() );
	}

	public function get_UtilityFeatures_for_post_type( String $post_type ) : array
	{


		// prepare array 
		$return = \wp_list_filter( 
			$this->get_UtilityFeatures(),
			[ 'post_type' => $post_type ]
		);

		// strip old indexex
		$return = array_values( $return );
	#	$return = \wp_filter_object_list( 
	#		$this->get_UtilityFeatures(),
	#		[ 'post_type' => $post_type ], 'and', 'value'
	#	);
		return $return;
		// return array_merge( [], $return );
	}

	public function get_UtilityFeatures_defaults( array $options ) : array
	{
		return \wp_filter_object_list( $options, [ 'default' => true ], 'and', 'value' );;
	}

	/**
	 * [get_UtilityFeatures_for_hm_ui description]
	 * @param  Array  $options   [description]
	 * @param  String $post_type [description]
	 * @return [type]            [description]
	 */
	private function get_UtilityFeatures_for_hm_ui( array $options, String $post_type ) : array 
	{
		$_pt = \get_post_type_object( $post_type );
		// get all default options 
		$_defaults = $this->get_UtilityFeatures_defaults( $options );
		// get all options recursively type casted as arrays
		$_options = \json_decode( \json_encode( $options ) , true);

		return [
			// THIS 'id' is important, to be found by JS // DO NOT CHANGE !!! I tried ;(
			'id'       => 'my-utility-options',
			'title'    => \sprintf(
				_x( 'Utility-Features of this %s', '%s = post_type singular name', 'figurentheater' ),
				$_pt->labels->singular_name
			),
			// add all default options 
			'defaults' => $_defaults, // Optional. This will assign new posts to the terms set here.
			// add all options 
			'options'  => $_options,
		];
	}

	public function debug()
	{
	#	$_die_dicke = \get_post( 378 );
	#	$_die_dicke_c = \get_the_content( null, false, $_die_dicke );
	#	$_die_dicke_p = \parse_blocks( $_die_dicke_c );
	#	$_die_dicke_r = render_block( $_die_dicke_p );

	#	\do_action( 'qm/debug', $_die_dicke_r );


		#global $typenow;
		#$post_type = (is_string($typenow)) ? $typenow : 'ft_site';
		#$options = [];
		#$options_of_pt = $this->get_UtilityFeatures_for_post_type( $post_type );
		#$options[] = $this->get_UtilityFeatures_for_hm_ui( $options_of_pt, $post_type );

		#\do_action( 'qm/debug', $this->get_UtilityFeatures() );
		#\do_action( 'qm/debug', is_array( $this->get_UtilityFeatures_for_post_type( 'ft_site' ) ) );
		// \do_action( 'qm/debug', \wp_list_filter( $this->get_UtilityFeatures(), [ 'post_type' =>  'ft_site' ] ) );
		#\do_action( 'qm/debug', is_array( $this->get_UtilityFeatures_for_post_type( 'ft_production' ) ) );
		// \do_action( 'qm/debug', \wp_list_filter( $this->get_UtilityFeatures(), [ 'post_type' =>  'ft_production' ] ) );
				
		// \do_action( 'qm/debug', \get_the_terms( \get_the_id(), self::TAX ) );
		// \do_action( 'qm/debug', $options_of_pt );
		// \do_action( 'qm/debug', $options );
		// \do_action( 'qm/debug', apply_filters( 'hm_utility_options', [], $post_type ), );
	}
}

















#add_action( 'enqueue_block_editor_assets', __NAMESPACE__.'\\debug_ft_features_mngmnt', 20);
#debug_ft_features_mngmnt();
function debug_ft_features_mngmnt(){

#	$_defaults = \wp_filter_object_list( $Taxonomie2->options, [ 'default' => true ], 'and', 'value' );
	// get all options recursively type casted as arrays
#	$_options = json_decode( json_encode($Taxonomie2->options) , true);

	wp_die(
		'<pre>'.
		var_export(
			array(
#				$Taxonomie2->get_UtilityFeatures_for_post_type( $Taxonomie2->options, 'ft_site'),
#				$_defaults,
				apply_filters( 'hm_utility_options', [], 'ft_site' ),
				\Figuren_Theater\API::get('UtilityFEAT')->get(),
#				Taxonomie::Init(),
#				$_defaults,
#				$_options,
			),
			true
		).
		'</pre>'
	);
}
