<?php
declare(strict_types=1);

namespace Figuren_Theater\inc\Geo;

use Figuren_Theater\Network\Taxonomies;


class ft_geo_options_bridge {


	public function __construct() {}


	/**
	 * [update_option_ft_geo description]
	 * 
	 * @param  mixed  $old_value   [description]
	 * @param  mixed  $new_value   [description]
	 * @param  string $option_name [Should be 'ft_geo' in every case. Because this is hooked to the 'update_option_ft_geo' action.]
	 * 
	 * @return [Array|false]              [Returns the updated 'ft_geo' option or FALSE on failure.]
	 */
	public function update_option_ft_geo(  mixed $old_data, mixed $new_data, string $option_name ) : array|false {

		// DEBUG
		#error_log("update_option_ft_geo: [New Data, Old Data]" );

		error_log(__FILE__);
		error_log(__FUNCTION__);
		error_log(var_export([$option_name, $new_data, $old_data], true ) );
		error_log(\wp_debug_backtrace_summary('WP_Hook'));
	
		/* Example
		array (
		  'page' => '23',
		  'country' => 'deu',
		  'legal_entity' => 'self',
		  'name' => 'Carsten Bach',
		  'address' => 'Friesenstrasse 8, 06112 Halle (Saale)',
		  'address_alternative' => '',
		  'email' => '',
		  'phone' => '',
		  'fax' => '',
		  'press_law_person' => 'Paul Panther',
		  'vat_id' => '',
		)*/

		$_country_helper = [
			'deu' => __('Deutschland','figurentheater'),
			'aut' => __('Österreich','figurentheater'),
			'che' => __('Schweiz','figurentheater'),
		];

		$ft_geo = [];

		// something went wrong, huh ??
		if (!isset($new_data['address']))
			return false;

		// want to delete former address
		// clear on empty adr
		if (empty($new_data['address'])){
			
			$empty = [
				'address',
				'geojson',
				'tax_terms'
			];
			
			\update_option( 'ft_geo', $empty, 'no' );
			
			return $empty;
		}

		// sanitize and persist incoming address (as is)
		$ft_geo['address'] = $new_data['address'] = \sanitize_textarea_field( $new_data['address'] );
		// $ft_geo['address'] = $new_data['address'];

		// Replace spaces and newlines with a comma
		$new_data['address'] = str_replace(array("\r\n","\r","\n"),', ',$new_data['address']);
		// remove double-comma, Nomniatim doesn't like it
		$new_data['address'] = str_replace(array(", , "),', ',$new_data['address']);
		// Replace "c/o" with nothing
		$new_data['address'] = str_replace(array("c/o"),'',$new_data['address']);

		// add Country to the search string
		// because the normal adr-field asks only for
		// street, number, postcode and city
		if ( isset( $new_data['country'] ) )
			$new_data['address'] = join(
				', ',
				[
					$new_data['address'],
					$_country_helper[ $new_data['country'] ]
				]
			);

		// DEBUG
		// error_log("the mangled and sanitized new_data['address'], ready to lookup the GeoJSON via API");
		// error_log(__FILE__);
		// error_log(__FUNCTION__);
		// error_log(var_export(\get_current_blog_id(),true ) );
		// error_log(var_export($new_data['address'],true ) );


		// we only want to store thoose transients on the main f.t
		$_coresites = array_flip( FT_CORESITES );
		\switch_to_blog( $_coresites['root'] );

		// 1. get geojson from Nominatim API
		$geojson = $this->get_geojson( $new_data['address'] );

		// something is wrong with this geojson
		if ( 
			!is_array($geojson)
			||
			empty($geojson)
			||
			!isset($geojson['properties'], $geojson['properties']['address'])
		) {
			\restore_current_blog();

			//
			error_log("[figuren.theater] ERROR Couldn't 'get_geojson()' for ");
			error_log(__FILE__);
			error_log(__FUNCTION__);
			error_log(var_export(\get_current_blog_id(),true ) );
			error_log(var_export($new_data['address'],true) );

			// set empty defaults to prevent later errors
			$ft_geo['geojson'] = $ft_geo['tax_terms'] = [];
			// and bye bye
			return ( \update_option( 'ft_geo', $ft_geo, 'no' ) ) ? $ft_geo : false;
		}

		// 2. add response to $ft_geo-Array
		$ft_geo['geojson'] = $geojson;

		// 3. Ask f.t for existing taxonomies matching the reponse' and get term-IDs for ...
		// 4. Or create them based on the geolocation-request and save newly created term-IDs
		// 
		// !!!
		// this is a figuren.theater-specific data - IS THIS THE RIGHT PLACE?
		$ft_geo['tax_terms'] = $this->get_geotax_terms( $geojson['properties']['address'] );



		//
		\restore_current_blog();


		# 4.1 Also create them locally, based on the same geolocation-request 
		# so we have the same slugs and the same hierachy as on f.t
		$_local_tax_terms = $this->get_geotax_terms( $geojson['properties']['address'] );

		// error_log("[figuren.theater] is processing the ADR into GEOTAX terms ");
		// error_log(__FILE__);
		// error_log(__FUNCTION__);
		// error_log(var_export(\get_current_blog_id(),true ) );
		// error_log(var_export($ft_geo['tax_terms'],true) );
		// error_log(var_export($_local_tax_terms,true) );

		//
		$_ft_geo_tax_name = Taxonomies\Taxonomy__ft_geolocation::NAME;
		// $_ft_geo_tax_slugs = array_values( \wp_list_pluck( $ft_geo['tax_terms'], 'slug' ) ); # === to using 'slug' with $_local_tax_terms
		$_ft_geo_term_tax_ids = array_values( \wp_list_pluck( $_local_tax_terms, 'term_taxonomy_id' ) );

		# 4.2...and save newly created term-IDs as defaults geo-tax for new posts|events etc.
		if ( is_array( $_ft_geo_term_tax_ids ) || !empty( $_ft_geo_term_tax_ids ) ) {
			// code...
			
			$__update_option = \update_option("default_{$_ft_geo_tax_name}_terms", $_ft_geo_term_tax_ids, 'no');
		
			// error_log("[figuren.theater] Result after updating the new default GEOTAX terms :");
			// error_log(__FILE__);
			// error_log(__FUNCTION__);
			// error_log(var_export($__update_option,true ) );

			# 4.3....and update our important 'ft_site'-post
			$_ft_site_post_id = \Figuren_Theater\FT::site()->get_site_post_id();
			\wp_set_object_terms( $_ft_site_post_id, $_ft_geo_term_tax_ids, $_ft_geo_tax_name, false ); # working, but only for the current, not all distributed sites

			# trigger syndication ...
			// $connection = new \Distributor\InternalConnections\NetworkSiteConnection( \get_site( $_coresites['root'] ) ); 
			$connection = new \Distributor\InternalConnections\NetworkSiteConnection( \get_site() );
			$connection->update_syndicated( $_ft_site_post_id );


		} else {
			error_log("[figuren.theater] ERROR Couldn't 'get_geotax_terms()' for ");
			error_log(__FILE__);
			error_log(__FUNCTION__);
			error_log(var_export($geojson['properties']['address'],true ) );

		}

		return ( \update_option( 'ft_geo', $ft_geo, 'no' ) ) ? $ft_geo : false;
	}

	/**
	 * Helper to do the geolocation request
	 * 
	 */
	public function get_geojson( string $address, $save_transient = true ) : array
	{

		$_transient_name = 'ft_geo_' . rawurlencode( $address ) ;
		$geojson   = \get_transient( $_transient_name );

		if( empty( $geojson ) || ! is_array( $geojson ) ) {

			// Make an AJAX call to Nominatim API and get a promise back.
			// almost the same as in js\ft_geolocation-acf.js

			// 'format' : 'geojson',			// This format follows the RFC7946. Every feature includes a bounding box (bbox).
			// 'addressdetails' : 1,			// Include a breakdown of the address into elements. (Default: 0)
			// 'accept-language' : 'de',		// Preferred language order for showing search results, overrides the value specified in the "Accept-Language" HTTP header. Either use a standard RFC2616 accept-language string or a simple comma-separated list of language codes.
			// 'countrycodes' : 'de,at,ch,lu',	// Limit search results to one or more countries. <countrycode> must be the ISO 3166-1alpha2 code
			// 'limit' : 1,						// Limit the number of returned results. (Default: 10, Maximum: 50)
			// 'viewbox' : <x1>,<y1>,<x2>,<y2>	// The preferred area to find search results. Any two corner points of the box are accepted in any order as long as they span a real box. x is longitude, y is latitude. // created with https://boundingbox.klokantech.com/
			// 'polygon_geojson' = 1,			// Output geometry of results as a GeoJSON, KML, SVG or WKT. Only one of these options can be used at a time. (Default: 0)
			// 'email' : 						// If you are making large numbers of request please include an appropriate email address to identify your requests. See Nominatim's Usage Policy for more details.
			// 'q' : ...						// Free-form query string to search for. Free-form queries are processed first left-to-right and then right-to-left if that fails. Commas are optional, but improve performance by reducing the complexity of the search.

			$api_args = array(
				'format'          => 'geojson',
				'addressdetails'  => 1,
				'accept-language' => 'de',
				'countrycodes'    => 'de,at,ch,lu',
				'limit'           => 1,
				'viewbox'         => '5.0489318371,17.8809630871,55.6603640848,45.3770058216',
				'polygon_geojson' => 1,
				'email'           => 'info+nominatim.openstreetmap.org@figuren.theater',
				'q'               => $address
			);

			$url  = 'https://nominatim.openstreetmap.org/?'.http_build_query( $api_args );

			$json = \wp_remote_get( $url );
			if ( 200 !== (int) \wp_remote_retrieve_response_code( $json ) ) {
				#error_log("[figuren.theater] ERROR Couldn't 'wp_remote_get()' for ");
				#error_log( var_export($url,true) );
				return [];
			} // repsonse OK ?

			$body = \wp_remote_retrieve_body( $json );
			$json = json_decode( $body, true );

			// do some simple validation
			// 
			// because we are not using the
			// heavy WP_GeoMeta-Lib anymore,
			// we need some simple replacement
			// if ( class_exists('WP_GeoUtil') && !WP_GeoUtil::is_geojson( $geojson ))
			// 	return false;
			if ( ! isset( $json['features'][0]['properties']['address'] ) || 
				   empty( $json['features'][0]['properties']['address'] ) || 
				   empty( $json['features'][0] ) 
				) {
				#error_log("[figuren.theater] ERROR Couldn't there was some JSON responded by Nominatim, but geojson['properties']['address'] is missing or empty.");
				#error_log( var_export([$json,$url],true) );
				return [];
			}

			$geojson = $json['features'][0];
			
			if (true === $save_transient)
				\set_transient( $_transient_name, $geojson, HOUR_IN_SECONDS ); // keep this only a short time, to help on multi-saving etc.


		} // has transient
		return $geojson;
	}



	public function get_geotax_terms( $address ) : array
	{
		// // DEBUG ONLY
		// if (!$address) {
		// 	$_addr = $this->get_geojson( 'Donauwörth', false );
		// 	$address = $_addr['properties']['address'];
		// $_coresites = array_flip( FT_CORESITES );
		// \switch_to_blog( $_coresites['root'] );
		// }


		$_tax_terms = array();

		//
		$terms_to_look_for = array( 'country' => $address['country'] );

		if (isset($address['state']))
			$terms_to_look_for['state'] = $address['state'];

		// prepare for 'Berlin', 'Hamburg', 'Bremen' and similiar ...
		if (!isset($terms_to_look_for['state']) && isset($address['city'])){
			$terms_to_look_for['state'] = $address['city'];
			unset($address['city']); // do not re-use anymore
		}


		//
		if (isset($address['county']) && $address['county'] != $terms_to_look_for['state'])
			$terms_to_look_for['county'] = $address['county'];

		//
		if (!isset($terms_to_look_for['county']) && isset($address['city']))
			$terms_to_look_for['county'] = $address['city'];

		//
		if (!isset($terms_to_look_for['county']) && isset($address['place']))
			$terms_to_look_for['county'] = $address['place'];

		foreach ($terms_to_look_for as $type => $term_to_look_for) {

			// gets WP_TERM object or FALSE
			$_term = \get_term_by( 'name', $term_to_look_for, Taxonomies\Taxonomy__ft_geolocation::NAME );

			// if term already exists
			// get its ID
			if( false !== $_term) {
				$_tax_terms[$type] = $_term;
			// otherwise
			// create a new tax term
			} else {
				$_tax_terms[$type] = $this->set_geotax_term( $term_to_look_for, $type, $address['country_code'], $_tax_terms );
			}
		}

		//DEBUG ONLY
		// restore_current_blog();

		return $_tax_terms;
	}



	public function set_geotax_term( $term_to_look_for, $type, $slug = '', $_tax_terms = '' ) {

		$_new_geotax_term_args = array();

		switch ($type) {
			case 'country':
				$_new_geotax_term_args['slug'] = $slug;
				break;
			case 'state':
				$_new_geotax_term_args['parent'] = $_tax_terms['country']->term_id;
				break;
			case 'county':
				$_new_geotax_term_args['parent'] = $_tax_terms['state']->term_id;
				break;
		}

		//
		$_new_geotax_term = \wp_insert_term( 
			$term_to_look_for,
			Taxonomies\Taxonomy__ft_geolocation::NAME,
			$_new_geotax_term_args
		);

		//
		return  \get_term(
			$_new_geotax_term['term_id'],
			Taxonomies\Taxonomy__ft_geolocation::NAME
		);
	}

}
