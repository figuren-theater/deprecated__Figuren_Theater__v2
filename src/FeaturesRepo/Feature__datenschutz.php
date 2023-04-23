<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\inc\EventManager;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Taxonomies;




use Figuren_Theater\Network\Blocks\Filtered_Links;



class Feature__datenschutz extends Features\Feature__Abstract implements EventManager\SubscriberInterface
{

	const SLUG = 'datenschutz';


	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			// 'parse_query' => 'enable_render_block_filter',
			// 'pre_get_posts' => 'enable_render_block_filter', // triggers errors in wp-includes/class-wp-query.php:4121ff on missing page-obj.
			'wp' => 'enable_render_block_filter', // nice :)
		);
	}

	public function enable() : void {}
	public function enable__on_admin() : void {}




	/**
	 * Hide Blocks based on loaded Features of this site
	 */
	/**
	 * Filters the content of a single block.
	 *
	 * The dynamic portion of the hook name, `$name`, refers to
	 * the block name, e.g. "core/paragraph".
	 *
	 * @since 5.7.0
	 * @since 5.9.0 The `$instance` parameter was added.
	 *
	 * @param string   $block_content The block content about to be appended.
	 * @param array    $block         The full block, including name and attributes.
	 * @param WP_Block $instance      The block instance.
	 */
	public function render_filter( string $block_content, array $block, \WP_Block $instance ) : string
	{
		// get current ft_site
		$ft_site = \Figuren_Theater\FT::site();

		$_has_social_links = (bool) Filtered_Links\get_relevant_links( 
			Taxonomies\Term__link_category__privacy::SLUG 
		);

		$_check_for = [
			'privacy-policy--social-networks' => $_has_social_links,
			'privacy-policy--comments'        => 'kommentare-ohne-spam',
			'privacy-policy--newsletter'      => 'newsletter',
			'privacy-policy--contact-forms'   => 'use-cf7',
		];
		foreach ($_check_for as $html_id => $ft_feature ) {

			// or block doesn't contain the searched html id attribute
			if ( ! (bool) strpos( $block['innerContent'][0], $html_id ) )
				continue;
			
			// we already know, we need this block
			if ( true === $ft_feature )
				return $block_content;

			// we already know, we don't need this block
			if ( false === $ft_feature )
				return '';

			// check, if this site
			// has the feature related to this block
			// and show the content if so
			if ( $ft_site->has_feature([ $ft_feature ]) )
				return $block_content;

			// hide all non-matching blocks 
			// by returning nothing on render
			return ''; 

		}
		return $block_content; // nothing to do, no html to filter

	}



	/**
	 * Hooks such as init will not work at all.
	 * You have to hook at least on parse_query.
	 *
	 * @return [type] [description]
	 */
	public function enable_render_block_filter()
	{

		if ( \is_admin() )
			return;

		if ( ! \is_page( 'Datenschutz' ) )
			return;

		\add_filter( 'render_block_core/group', [ $this, 'render_filter' ], 10, 3 );
	}


}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
