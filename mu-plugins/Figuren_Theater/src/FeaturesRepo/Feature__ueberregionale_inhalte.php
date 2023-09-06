<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;
use Figuren_Theater\Network\Taxonomies;


class Feature__ueberregionale_inhalte extends Features\Feature__Abstract
{

	const SLUG = 'ueberregionale-inhalte';

	public function enable() : void 
	{

		\add_action( 'init', [$this, 'modify_Post_Type__ft_production'], 5); // must be between 0 and 10
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_geolocation'], 5); // must be between 0 and 10


		// \add_action( 'init', [$this, 'add_rewrite_rules_for_shared_tax'], 5); // must be between 0 and 10

	}

	public function modify_Post_Type__ft_production()
	{
	
		$PT_ft_production = \Figuren_Theater\API::get('PT')->get( Post_Types\Post_Type__ft_production::NAME );

	#	$_taxonomies    = array_merge( $PT_ft_production->args['taxonomies'], [ 
	#			Taxonomies\Taxonomy__ft_geolocation::NAME
	#		] );
		$_admin_cols    = array_merge( $PT_ft_production->args['admin_cols'], [
				Taxonomies\Taxonomy__ft_geolocation::NAME => [
					'taxonomy' => Taxonomies\Taxonomy__ft_geolocation::NAME
				],
			] );
		$_admin_filters = array_merge( $PT_ft_production->args['admin_filters'], [
				Taxonomies\Taxonomy__ft_geolocation::NAME => [
					'title'    => __('All Locations', 'figurentheater'),
					'taxonomy' => Taxonomies\Taxonomy__ft_geolocation::NAME
				],
			] );

		\Figuren_Theater\API::get('PT')->update( Post_Types\Post_Type__ft_production::NAME, 'args', [

			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,

			// 'taxonomies'          => $_taxonomies,

			# Add the post type to the site's main RSS feed:
			'show_in_feed' => false,
			#
			'quick_edit' => false,

			'admin_cols' => $_admin_cols,

			# Add some dropdown filters to the admin screen:
			'admin_filters' => $_admin_filters,
		] );
	}



	public function enable__on_admin() : void 
	{
	}



	/**
	 * This taxonomy is loaded all over the network,
	 * the UI and also its automatic shadowing
	 * is only load on https://websites.fuer.figuren.theater 
	 * and https://mein.figuren.theater 
	 */
	public function modify_Taxonomy__ft_geolocation()
	{
		\add_action('admin_head-index.php', [ \Figuren_Theater\API::get('TAX')->get( Taxonomies\Taxonomy__ft_geolocation::NAME ), 'show_icon_at_a_glance' ] );

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_geolocation::NAME, 'args', [
			'public' => true,
			'show_ui' => true, // set this, because the TAX defines this hard, so ...
			// 'show_tagcloud' => true,
			// 'show_in_nav_menus' => true,
			'show_admin_column'  => true,
			'rewrite'       => array(
				'slug'                       => Taxonomies\Taxonomy__ft_geolocation::SLUG,
				'with_front'                 => false,
				'hierarchical'               => true,
			),
			'dashboard_glance' => true,

		] );
	}
	

	#############################################################
	#### Working, but ...
	#### ugly coded and needs improvement
	#############################################################

	public function add_rewrite_rules_for_shared_tax() {

		global $wp_rewrite;

		// all post_types that share this LOCATION taxonomy
		$post_types = join('|', array('blog','events/event') ); # TODO

		// rewrite slug of this tax
		$_tax_slug = Taxonomies\Taxonomy__ft_geolocation::SLUG;
		//
		$_tax_name = Taxonomies\Taxonomy__ft_geolocation::NAME;

		// first try, not bad
#		add_rewrite_rule( '('.$post_types.')/'.$_tax_slug.'/([^/]+)/?', 'index.php?post_type=$matches[1]&'.$_tax_name.'=$matches[2]', 'top' );
#		add_rewrite_rule( '('.$post_types.')/'.$_tax_slug.'/([^/]+)/page/?([0-9]{1,})/?', 'index.php?post_type=$matches[1]&'.$_tax_name.'=$matches[2]&paged=$matches[3]', 'top' );
#		add_rewrite_rule( '('.$post_types.')/'.$_tax_slug.'/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?', 'index.php?post_type=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
#		add_rewrite_rule( '('.$post_types.')/'.$_tax_slug.'/([^/]+)/(feed|rdf|rss|rss2|atom)/?', 'index.php?post_type=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );


##		add_rewrite_rule( '(blog|jobs|festivals)/'.$_tax_slug.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
##		add_rewrite_rule( '(blog|jobs|festivals)/'.$_tax_slug.'/(.+?)/(feed|rdf|rss|rss2|atom)/?', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
##		add_rewrite_rule( '(blog|jobs|festivals)/'.$_tax_slug.'/(.+?)/embed/?$', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&embed=true', 'top' );
##		add_rewrite_rule( '(blog|jobs|festivals)/'.$_tax_slug.'/(.+?)/seite/?([0-9]{1,})/?', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&paged=$matches[3]', 'top' );
##		// have this last, to make sure that
##		// e.g. '/feed/' doesn't collide with 
##		// its hierachical location terms 
##		add_rewrite_rule( '(blog|jobs|festivals)/'.$_tax_slug.'/(.+?)/?$', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]', 'top' );

#wp_die( $wp_rewrite->get_tag_permastruct() );
if ($tag_permastruct = $wp_rewrite->get_tag_permastruct()) {
		// remove rewrite_tag, that will be taken by regex
		$tag_permastruct = str_replace('/%post_tag%', '', $tag_permastruct);

		//
		add_rewrite_rule( $tag_permastruct.'/([^/]+)/'.$_tax_slug.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?', 'index.php?tag=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
		add_rewrite_rule( $tag_permastruct.'/([^/]+)/'.$_tax_slug.'/(.+?)/(feed|rdf|rss|rss2|atom)/?', 'index.php?tag=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
		add_rewrite_rule( $tag_permastruct.'/([^/]+)/'.$_tax_slug.'/(.+?)/embed/?$', 'index.php?tag=$matches[1]&'.$_tax_name.'=$matches[2]&embed=true', 'top' );
		add_rewrite_rule( $tag_permastruct.'/([^/]+)/'.$_tax_slug.'/(.+?)/seite/?([0-9]{1,})/?', 'index.php?tag=$matches[1]&'.$_tax_name.'=$matches[2]&paged=$matches[3]', 'top' );
		// have this last, to make sure that
		// e.g. '/feed/' doesn't collide with 
		// its hierachical location terms 
		add_rewrite_rule( $tag_permastruct.'/([^/]+)/'.$_tax_slug.'/(.+?)/?$', 'index.php?tag=$matches[1]&'.$_tax_name.'=$matches[2]', 'top' );
}

		add_rewrite_rule( '(.+?)/'.$_tax_slug.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
		add_rewrite_rule( '(.+?)/'.$_tax_slug.'/(.+?)/(feed|rdf|rss|rss2|atom)/?', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&feed=$matches[3]', 'top' );
		add_rewrite_rule( '(.+?)/'.$_tax_slug.'/(.+?)/embed/?$', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&embed=true', 'top' );
		add_rewrite_rule( '(.+?)/'.$_tax_slug.'/(.+?)/seite/?([0-9]{1,})/?', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]&paged=$matches[3]', 'top' );
		// have this last, to make sure that
		// e.g. '/feed/' doesn't collide with 
		// its hierachical location terms 
		add_rewrite_rule( '(.+?)/'.$_tax_slug.'/(.+?)/?$', 'index.php?category_name=$matches[1]&'.$_tax_name.'=$matches[2]', 'top' );



# has a post setup, but redirects to its URL
#add_rewrite_rule( $tag_permastruct.'/([^/]+)/in/?$', 'index.php?tag=$matches[1]&pagename=in&ft_geo_in=1', 'top' );
#add_rewrite_rule( '(.+?)/in/?$', 'index.php?category_name=$matches[1]&pagename=in&ft_geo_in=1', 'top' );
#add_rewrite_rule( 'in/?$', 'index.php?pagename=in&ft_geo_in=1', 'top' );


add_rewrite_rule( $tag_permastruct.'/([^/]+)/in/?$', 'index.php?tag=$matches[1]&ft_geo_in=1', 'top' );
add_rewrite_rule( '(.+?)/in/?$', 'index.php?category_name=$matches[1]&ft_geo_in=1', 'top' );
add_rewrite_rule( 'in/?$', 'index.php?ft_geo_in=1', 'top' );
	}


}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
