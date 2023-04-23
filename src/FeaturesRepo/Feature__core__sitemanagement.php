<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Coresites\Post_Types as Coresites_Post_Types;

use Figuren_Theater\Network\Taxonomies;
use Figuren_Theater\Network\Features;

/**
 * Replacement for is_ft_core_site('webs')
 */
class Feature__core__sitemanagement extends Features\Feature__Abstract
{
	const SLUG = 'core-sitemanagement';

	public function enable() : void 
	{
		//
		// New way w/o PluginsManager
		// ugly, but working (FOR NOW)
		require WP_PLUGIN_DIR . '/ft_sales/ft_sales.php';
		require WP_PLUGIN_DIR . '/ft-core-featuretable/plugin.php';

		// 		
		$this->load_post_types();
		// 
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_feature_shadow'], 5); // must be between 0 and 10
		\add_action( 'init', [$this, 'modify_Taxonomy__ft_level_shadow'], 5); // must be between 0 and 10

	}

	protected function load_post_types()
	{
		// Register 'Level' PT
		$Post_Type__ft_level = Coresites_Post_Types\Post_Type__ft_level::get_instance();
		\Figuren_Theater\API::get('PT')->add( Coresites_Post_Types\Post_Type__ft_level::NAME, $Post_Type__ft_level );
		
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $Post_Type__ft_level );
		
		// Register 'Feature' PT
		$Post_Type__ft_feature = Coresites_Post_Types\Post_Type__ft_feature::get_instance();
		\Figuren_Theater\API::get('PT')->add( Coresites_Post_Types\Post_Type__ft_feature::NAME, $Post_Type__ft_feature );

		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $Post_Type__ft_feature );
		
		// Register 'Theme' PT
		$Post_Type__ft_theme = Coresites_Post_Types\Post_Type__ft_theme::get_instance();
		\Figuren_Theater\API::get('PT')->add( Coresites_Post_Types\Post_Type__ft_theme::NAME, $Post_Type__ft_theme );

		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $Post_Type__ft_theme );

	}



	/**
	 * This taxonomy is loaded all over the network,
	 * the UI and also its automatic shadowing
	 * is only load on https://websites.fuer.figuren.theater 
	 * and https://mein.figuren.theater 
	 */
	public function modify_Taxonomy__ft_feature_shadow()
	{
		// Register shadow connection between this taxonomy and post_type
		$ft_feature__TAX_shadow = new Taxonomies\TAX_Shadow( 
			Taxonomies\Taxonomy__ft_feature_shadow::NAME, 
			Coresites_Post_Types\Post_Type__ft_feature::NAME
		);
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $ft_feature__TAX_shadow );


		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_feature_shadow::NAME, 'post_types', [
			'post',
			Coresites_Post_Types\Post_Type__ft_level::NAME
		] );

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_feature_shadow::NAME, 'args', [
			'show_ui' => true,
			'show_admin_column' => true,
		] );
	}



	/**
	 * This taxonomy is loaded all over the network,
	 * the UI and also its automatic shadowing
	 * is only load on https://websites.fuer.figuren.theater 
	 * and https://mein.figuren.theater 
	 */
	public function modify_Taxonomy__ft_level_shadow()
	{
		// Register shadow connection between this taxonomy and post_type
		$ft_level__TAX_shadow = new Taxonomies\TAX_Shadow( 
			Taxonomies\Taxonomy__ft_level_shadow::NAME, 
			Coresites_Post_Types\Post_Type__ft_level::NAME
		);
		\Figuren_Theater\FT::site()->EventManager->add_subscriber( $ft_level__TAX_shadow );


		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_level_shadow::NAME, 'post_types', [
			'post',
		] );

		\Figuren_Theater\API::get('TAX')->update( Taxonomies\Taxonomy__ft_level_shadow::NAME, 'args', [
			'show_ui' => true,
			'show_admin_column' => true,
		] );
	}




	public function enable__on_admin() : void 
	{
		// TODO // queue as async jobs to prevent timeouts
		add_action( 'load-edit.php', [$this, 'update_DB_from_feature_files'] );
		add_action( 'load-edit.php', [$this, 'update_DB_from_theme_files'] );

		// $this->debug(); 
	}

	public function update_DB_from_feature_files()
	{
		global $typenow;

		// not an admin-listing for our PT
		if (Coresites_Post_Types\Post_Type__ft_feature::NAME !== $typenow)
			return;
		// \do_action( 'qm/debug', 'Going to update_DB_from_feature_files()' );   // https://querymonitor.com/docs/logging-variables/

		// 0. Init our WP_Query wrapper
		$ft_query = \Figuren_Theater\FT_Query::init();

		// 1. get all (already existing) features from DB
		$_db_features_objs = $ft_query->find_many_by_type( Coresites_Post_Types\Post_Type__ft_feature::NAME );
		// \do_action( 'qm/debug', $_db_features_objs );   // https://querymonitor.com/docs/logging-variables/
		
		// 1.1. Get only the slugs from the full WP_Post objects
		$_db_features = \wp_filter_object_list( $_db_features_objs, array(), 'and', 'post_name' );
		// \do_action( 'qm/debug', $_db_features );   // https://querymonitor.com/docs/logging-variables/

		// make sure we have something to compare against
		// otherwise our mechanism maybe would create new not-needed posts
		if( !is_array( $_db_features ) || empty( $_db_features ) )
			return;

		// 2. get all normal(!) Features, not UtilityFeatures, from the collection|files
		$_file_features = \Figuren_Theater\API::get('FEAT')->get(); 
		// \do_action( 'qm/debug', \array_keys( $_file_features ) );   // https://querymonitor.com/docs/logging-variables/

		// 3. compare db and file features to each other and ... 
		$_create_from = \array_diff( \array_keys( $_file_features ), $_db_features );
		// \do_action( 'qm/debug', $_create_from );   // https://querymonitor.com/docs/logging-variables/
		
		//    ... if there are any differences ...
		if (empty($_create_from))
			return;

		// 4. ... create new 'ft_feature'-posts from difference
		array_map(
			function( $_feature_slug ) use ( $ft_query )
			{
				$_new_feature = new Coresites_Post_Types\Post_Type__ft_feature( $_feature_slug );
				// \do_action( 'qm/debug', \wp_slash( $_new_feature->get_post_data() ) );   // https://querymonitor.com/docs/logging-variables/
				// $ft_query->save( $_new_feature );
				$return = $ft_query->save( $_new_feature );
				// \do_action( 'qm/debug', $return );   // https://querymonitor.com/docs/logging-variables/
			}, 
			$_create_from
		);

	}
	public function update_DB_from_theme_files()
	{
		global $typenow;

		// not an admin-listing for our PT
		if (Coresites_Post_Types\Post_Type__ft_theme::NAME !== $typenow)
			return;

		// \do_action( 'qm/debug', 'Going to update_DB_from_theme_files()' );   // https://querymonitor.com/docs/logging-variables/

		// 0. Init our WP_Query wrapper
		$ft_query = \Figuren_Theater\FT_Query::init();

		// 1. get all (already existing) features from DB
		$_db_themes_objs = $ft_query->find_many_by_type( Coresites_Post_Types\Post_Type__ft_theme::NAME );
		// \do_action( 'qm/debug', $_db_themes_objs );   // https://querymonitor.com/docs/logging-variables/
		
		// 1.1. Get only the slugs from the full WP_Post objects
		$_db_themes = \wp_filter_object_list( $_db_themes_objs, array(), 'and', 'post_name' );
		// \do_action( 'qm/debug', $_db_themes );   // https://querymonitor.com/docs/logging-variables/

		// make sure we have something to compare against
		// otherwise our mechanism maybe would create new not-needed posts
	#	if( !is_array( $_db_themes ) || empty( $_db_themes ) )
	#		return;

		// 2. get all themes 
		$_file_themes = \wp_get_themes();

		// 2.1 reduce to themes from the parent-themes folder
		$_filter_args = [
			'theme_root' => \WP_CONTENT_DIR . '/parent-themes',
		];
		$_file_themes = wp_list_filter( $_file_themes, $_filter_args, 'AND' );
		
		// 2.2 get theme slugs
		// \do_action( 'qm/debug', $_file_themes );   // https://querymonitor.com/docs/logging-variables/

		// 3. compare db and file themes to each other and ... 
		$_create_from = \array_diff( \array_keys( $_file_themes ), $_db_themes );
		// \do_action( 'qm/debug', $_create_from );   // https://querymonitor.com/docs/logging-variables/
		
		//    ... if there are no differences, bail ...
		if (empty($_create_from))
			return;

		// 4. ... create new 'ft_theme'-posts from difference
		array_map(
			function( $_theme_slug ) use ( $ft_query, $_file_themes )
			{

				$_new_theme = new Coresites_Post_Types\Post_Type__ft_theme( $_file_themes[ $_theme_slug ] );
				// \do_action( 'qm/debug', \wp_slash( $_new_theme->get_post_data() ) );   // https://querymonitor.com/docs/logging-variables/

				$return = $ft_query->save( $_new_theme );
				// \do_action( 'qm/debug', $return );   // https://querymonitor.com/docs/logging-variables/
				// 
				if (! empty( $return )) {
					$_new_theme->set_post_thumbnail( $return );
				}
			}, 
			$_create_from
		);

	}


	public function debug()
	{
		// get all themes
 /*
		$themes = \wp_get_themes();

		// reduce to themes from the parent-themes folder
		$_filter_args = [
			'theme_root' => \WP_CONTENT_DIR . '/parent-themes',
		];
		$themes = wp_list_filter( $themes, $_filter_args, 'AND' );
		
		// save 'theme' post
		*/
	
		// sprechbalsen im dialog
		// $metadata = \wp_get_attachment_metadata( 540 );

		// \do_action( 'qm/debug', $metadata );
		



		// $_new_theme = new Coresites_Post_Types\Post_Type__ft_theme( \wp_get_theme('twentytwentytwo') );

		// \do_action( 'qm/debug', $_new_theme );
		// \do_action( 'qm/debug', $_new_theme->set_post_thumbnail( 666 ) );
		
		\do_action( 'qm/debug', \get_theme_root() );
		\do_action( 'qm/debug', \get_theme_roots() );
	}
}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
