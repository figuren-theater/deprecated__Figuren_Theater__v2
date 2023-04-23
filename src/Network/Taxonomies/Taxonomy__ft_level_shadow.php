<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;

use Figuren_Theater\Network\Post_Types;


/**
 * This taxonomy is loaded all over the network,
 * the UI and also its automatic shadowing
 * is only load on https://websites.fuer.figuren.theater 
 * and https://mein.figuren.theater
 */
class Taxonomy__ft_level_shadow extends Taxonomy__Abstract implements Taxonomy__LinksShadowedPost__Interface
{


	/**
	 * Our growing up post_type
	 */
	const NAME = 'ft_level_shadow';

	/**
	 * The Rewrite Slug
	 */
	// const SLUG = '';

	protected $menu_icon_charcode = 'f174';

	protected function prepare_tax() : void {}

	protected function prepare_post_types() : array
	{
		$this->post_types[] = Post_Types\Post_Type__ft_site::NAME;

		return $this->post_types;
	}


	protected function prepare_labels() : array
	{
		return $this->labels = [
			# Override the base names used for labels:
			'singular' => __('Level','figurentheater'),
			'plural'   => __('Level','figurentheater'),
			'slug'     => '' // must be a string, so an empty one
		];
	}

	protected function register_taxonomy__default_args() : array
	{
		return [
			'label'         => $this->labels['plural'], // fallback
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => false,
			'hierarchical'  => false,
			'show_tagcloud' => false,
			'show_in_nav_menus' => false,
			'show_in_rest'  => true,
			'show_admin_column'  => false,
			'query_var'       => false,
			'rewrite'       => false,
			'capabilities' => array(
				// 'manage_terms'  =>   'manage_categories',
				'manage_terms'  =>   'manage_'.$this::NAME,
				'edit_terms'    =>   'edit_' . $this::NAME, // this should only be done by the CRON
				'delete_terms'  =>   'delete_' . $this::NAME, // this should only be done by the CRON
				// 'assign_terms'  =>   'assign_'.$this->tax,
				'assign_terms'  =>   'manage_sites',
			),
		];

	}


	/**
	 * Default arguments for custom taxonomies
	 * Several of these differ from the defaults in WordPress' register_taxonomy() function.
	 * 
	 * https://github.com/johnbillion/extended-cpts/wiki/Registering-taxonomies#default-arguments-for-custom-taxonomies
	 */
	protected function register_extended_taxonomy__args() : array
	{
		return [
			# Use radio buttons in the meta box for this taxonomy on the post editing screen:
			// 'meta_box' => 'simple', //KEEP DISABLED // triggers JS problems in Gutenberg when editing 'ft_site'

			# Show this taxonomy in the 'At a Glance' dashboard widget:
			'dashboard_glance' => false,

			# Add a custom column to the admin screen:
			#'admin_cols' => [
			#	'updated' => [
			#		'title'       => 'Updated',
			#		'meta_key'    => 'updated_date',
			#		'date_format' => 'd/m/Y'
			#	],
			#],
		];
	}


} // END Class Taxonomy__ft_level_shadow
