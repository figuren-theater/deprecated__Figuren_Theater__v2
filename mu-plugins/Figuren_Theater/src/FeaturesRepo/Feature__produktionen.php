<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;
use Figuren_Theater\Network\Post_Types;

use Figuren_Theater\Performance\PWA;


class Feature__produktionen extends Features\Feature__Abstract {

	const SLUG = 'produktionen';

	public function enable() : void {

		/*\add_filter( 
			'Figuren_Theater\\Network\\Plugins\\required_plugins\\feature_required', 
			function( array $required_plugins ) : array {
				return array_merge(
					$required_plugins,
					[
						'theatrebase-production-subsites/theatrebase-production-subsites.php',
						'theatrebase-production-blocks/theatrebase-production-blocks.php',
					]
				);
			}
		);*/

		// Wrapper for some blocks and 
		// kinda default plugins
		// 
		// New way w/o PluginsManager
		// ugly, but working (FOR NOW)
		// 
		// DEACTIVATED 11/2024
		// (SoC) now loaded as post type support, from ft-theater
		// require WP_PLUGIN_DIR . '/theatrebase-production-subsites/theatrebase-production-subsites.php';
		// 
		// (SoC) moved into 'theater-production-blocks' plugin
		// require WP_PLUGIN_DIR . '/theatrebase-production-blocks/theatrebase-production-blocks.php';

		// \add_filter( 'web_app_manifest', __NAMESPACE__ . '\\web_app_manifest' )
		\add_filter( 'web_app_manifest', [ $this, 'web_app_manifest' ] );

		\add_action( 'init', [ $this, 'show_PT__ft_production' ], 5 ); // must be between 0 and 10
	}
	public function enable__on_admin() : void {}

	public function disable() : void {
		// \add_action( 'init', [ $this, 'hide_PT__ft_production' ], 5 ); // must be between 0 and 10
	}

	public function show_PT__ft_production() {
		\Figuren_Theater\API::get( 'PT' )->update( 
			Post_Types\Post_Type__ft_production::NAME, 
			'args', 
			[
				'public'             => true,
				'rewrite'            => [
					'slug'              => Post_Types\Post_Type__ft_production::SLUG,
					'with_front'        => false,
					'pages'             => true,
					'feeds'             => true,
					'hierarchical'      => true,
				],
				'has_archive'        => Post_Types\Post_Type__ft_production::SLUG,
				'dashboard_activity' => true,
				'dashboard_glance'   => true,
				'show_in_feed'       => true,
			]
		);
	}
/*
	public function hide_PT__ft_production() {
		\Figuren_Theater\API::get( 'PT' )->update( 
			Post_Types\Post_Type__ft_production::NAME, 
			'args', 
			[
				'public'             => false,
				'rewrite'            => false,
				'dashboard_activity' => false,
				'dashboard_glance'   => false,
				'show_in_feed'       => false,
			]
		);
	}*/

	/**
	 * Enables overriding the manifest json.
	 *
	 * There are more possible values for this, including 'orientation' and 'scope.'
	 * See the documentation: https://developers.google.com/web/fundamentals/web-app-manifest/
	 *
	 * @param array $manifest The manifest to send in the REST API response.
	 */
	public function web_app_manifest( array $manifest ) : array {

		$_production_pt_archive = \home_url( Post_Types\Post_Type__ft_production::SLUG );

		if ( ! isset( $manifest['shortcuts'] ) )
			$manifest['shortcuts'] = [];

		$manifest['shortcuts'][] = [
			'name'        => \__( 'Produktionen', 'figurentheater' ),
			'url'         => $_production_pt_archive,
			'description' => \__( 'Neuste Produktionen', 'figurentheater' ),

			// Icons 2 SVG 2 data-uri
			// https://icon-sets.iconify.design/dashicons/art/
			'icons'       => [
				[
					'src'     => \WP_CONTENT_URL . '/mu-plugins/Figuren_Theater/assets/svg/art.svg',
					'type'    => 'image/svg+xml',
					'purpose' => 'any monochrome',
				],
			],
		];


		if ( ! isset( $manifest['screenshots'] ) )
			$manifest['screenshots'] = [];

		$manifest['screenshots'][] = [
			// 'src'   => \BrowserShots::get_shot( \home_url('/produktionen/'), 900, 2000 ),
			'src'   => PWA\get_shot( 
				$_production_pt_archive, 
				900, 
				2000, 
				Post_Types\Post_Type__ft_production::SLUG
			),
			'sizes' => '900x2000',
			'type'  => 'image/jpeg',
		];

		return $manifest;
	}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
