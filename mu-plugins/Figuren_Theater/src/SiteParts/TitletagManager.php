<?php
/**
 * This file contains the
 * prototype of a
 * Title-tag Manager and
 * its related interfaces and (sub-) classes
 *
 * @package FT_PROTOTYPE_TITLE_TAG_MANAGER
 * @version 2022.04.14
 * @author  Carsten Bach
 */

declare(strict_types=1);

namespace Figuren_Theater\SiteParts;

use Figuren_Theater\Options;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Taxonomies;

/**
 * Prototyp für eine Figuren_Theater\Data\TitletagManager Klasse
 *
 * 
 * load on init to be available on frontend and backend
 * 
 * run on Data (post_type or taxonomy) registration
 * 
 * ask for interface
 * 
 * update option wpseo_titles
 * by APPENDING get_wpseo_titles() from Data
 */
class TitletagManager 
{

	const WPSEO_TITLES_OPTION = 'wpseo_titles';


	protected $wpseo_titles;
	

	protected $defaults = [];


	protected $args = [];


	/**
	 * Could be either 'pt' or 'tax'
	 * originated from 'Yoast SEO' Plugin
	 * 
	 * @var string
	 */
	protected $data_type = '';


	/**
	 * Name aka slug 
	 * the post_type or taxonomy 
	 * is registered with
	 * 
	 * @var string
	 */
	protected $data_name = '';



	protected $options = null;



	private function __construct()
	{
		$this->defaults = [
			'title'                        => '%%title%% %%page%% %%sep%% %%sitename%%',
			'metadesc'                     => '%%excerpt%%',
			'display-metabox'              => true,  // show some metabox for this data
			'noindex'                      => false, // prevent robots indexing
		];
		$this->pt_defaults = [
			'maintax'                      => 0,
			'schema-page-type'             => 'WebPage',
			'schema-article-type'          => 'None',
			'social-title'                 => '%%title%% %%sep%% %%sitename%%',
			'social-description'           => '%%excerpt%%',
			'social-image-url'             => '',
			'social-image-id'              => 0,
			'title-ptarchive'              => '%%archive_title%% %%page%% %%sep%% %%sitename%%',
			'metadesc-ptarchive'           => '',
			'bctitle-ptarchive'            => '',
			'noindex-ptarchive'            => false,
			'social-title-ptarchive'       => '%%archive_title%% %%sep%% %%sitename%%',
			'social-description-ptarchive' => '',
			'social-image-url-ptarchive'   => '',
			'social-image-id-ptarchive'    => 0,
		];
		$this->tax_defaults = [
			'ptparent'                     => 0, // 
		];
	}



	public function add_data( Data__CanAddYoastTitles__Interface $data )
	{

		if ( ! $this->is_allowed( $data ) )
			return; # without anything done

		// every time this fn runs
		// we need to 'unset' our previous data
		$this->options = null;

		$_type_defaults = $this->data_type . '_defaults';
		$_all_defaults  = $this->defaults + $this->$_type_defaults;

		$this->args = \wp_parse_args( 
			//
			$data->get_wpseo_titles(), 
			// 
			$_all_defaults
		);
		// \do_action( 'qm/debug', $data->get_wpseo_titles() );
		// \do_action( 'qm/debug', $this->get_options() );


		$this->data_name = $data::NAME;

		// update options array
		$this->update_wpseo_titles_option(
			$this->get_options()
		);
	}


	public function add_variables( Data__CanAddYoastVariables__Interface $data )
	{

		if ( function_exists('wpseo_register_var_replacement') )
		{
			// \do_action( 'qm/debug', $data );
			// \do_action( 'qm/debug', $data->get_wpseo_variables() );


			$wpseo_variables = $data->get_wpseo_variables();
			\add_action( 
				'wpseo_register_extra_replacements',
				function() use ( $wpseo_variables )
				{

					// Run on every element of our collection
					array_map(
						// do not set any Interace on this $collection_el
						// because it could be 'Taxonomy__CanInitEarly__Interface' 
						// and '\WP_Taxonomy' also
						function( $wpseo_variable )
						{
							\wpseo_register_var_replacement( 
								$wpseo_variable[0],
								$wpseo_variable[1],
								$wpseo_variable[2],
								$wpseo_variable[3],
								// '%%ftdebugme%%',
								// function()
								// {
								// 	return '22 FT DEBUG ME !!!!';
								// },
								// 'advanced', // OR 'basic',
								// 'ft DEBUGME some help text'
							); 
						},
						// Runs on all elements,
						$wpseo_variables
					);
				}
			);
		}
	}



	protected function is_allowed( mixed $data ) : bool
	{
		if ( $data instanceof Post_Types\Post_Type__Abstract )
		{
			$this->data_type = 'pt';
			return true;
		}

		// if ( 1==1 ) // DEBUG
		if ( $data instanceof Taxonomies\Taxonomy__Abstract)
		{
			$this->data_type = 'tax';
			return true;
		}

		return false;
	}



	protected function update_wpseo_titles_option( array $new_options )
	{
		// \do_action( 'qm/debug', $new_options );
		
		// if ( ! $this->wpseo_titles ) 
			$this->wpseo_titles = \Figuren_Theater\API::get('Options')->get( "option_" . self::WPSEO_TITLES_OPTION );
	
		if ( $this->wpseo_titles instanceof Options\Option )
		{
			$this->wpseo_titles->set_value( 
				$this->wpseo_titles->value + $new_options
			);
			// \do_action( 'qm/debug', $this->wpseo_titles );
		}
	}



	public function get_options() : array
	{
		// minimal caching
		if ( is_array( $this->options ) )
			return $this->options;

		return $this->set_options( $this->args ); 
	}



	protected function set_options( array $args ) : array
	{

		$this->options = [];

		array_walk( 
			$args,
			[ $this, 'set_option_key']
		);
		
		return $this->options;
	}



	protected function set_option_key( mixed $option_value, string $option_key )
	{
		$this->options[ $this->get_option_key( $option_key ) ] = $option_value;
	}

	
	/**
	 * Create 'Yoast SEO' style option-key 
	 * for the 'wpseo_titles' options array
	 *
	 * @package FT_PROTOTYPE_TITLE_TAG_MANAGER
	 * @version 2022.04.14
	 * @author  Carsten Bach
	 *
	 * @param   string       $option_key one of the keys defined in the CLASS::__constructor
	 * 
	 * @return  string                   modified key that fits yoasts internal schema
	 */
	protected function get_option_key( string $option_key ) : string
	{

		switch ( $option_key ) {
			
			case 'display-metabox':
				return self::sanitize_option_key( [ $option_key, $this->data_type, $this->data_name ] );

			case 'ptparent': // tax only
				return self::sanitize_option_key( [ 'taxonomy', $this->data_name, $option_key ] );

			case 'maintax': // pt only
				return self::sanitize_option_key( [ 'post_types', $this->data_name, $option_key ] );

			case 'title':
			case 'title-ptarchive': // pt only
			case 'metadesc':
			case 'noindex':

			case 'schema-page-type': // pt only
			case 'schema-article-type': // pt only
			case 'social-title': // pt only
			case 'social-description': // pt only
			case 'social-image-url': // pt only
			case 'social-image-id': // pt only
			case 'title-ptarchive': // pt only
			case 'metadesc-ptarchive': // pt only
			case 'bctitle-ptarchive': // pt only
			case 'noindex-ptarchive': // pt only
			case 'social-title-ptarchive': // pt only
			case 'social-description-ptarchive': // pt only
			case 'social-image-url-ptarchive': // pt only
			case 'social-image-id-ptarchive': // pt only
			default:
				if ( 'pt' === $this->data_type )
					return self::sanitize_option_key( [ $option_key, $this->data_name ] );
				if ( 'tax' === $this->data_type )
					return self::sanitize_option_key( [ $option_key, $this->data_type, $this->data_name ] );
		}
	}



	protected static function sanitize_option_key( array $key_parts ) : string
	{
		return join('-', $key_parts );
	}




	public static function init()
	{
		static $instance;

		if ( NULL === $instance ) {
			$instance = new self;
		}

		return $instance;
	}
}
