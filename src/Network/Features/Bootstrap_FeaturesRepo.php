<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Features;

use Figuren_Theater\FeaturesRepo;


/**
 * Bootstrap Repo Folder to the Autoloader
 */
class Bootstrap_FeaturesRepo {

	/**
	 * The folders, our bootstrapper will load the files from.
	 * 
	 * @var array
	 */
	protected $repos = [];

	/**
	 * The slug of the collection, to which 
	 * all loaded components will be added.
	 *
	 * This must not be the name of the collection class,
	 * but the name it is referenced in \Figuren_Theater::API with.
	 * 
	 * @var string
	 */
	protected $collection_identifier = '';

	/**
	 * [__construct description]
	 *
	 * @package project_name
	 * @version version
	 * @author  Carsten Bach
	 *
	 * @param   array  $repos                 [description]
	 * @param   string $collection_identifier [description]
	 */
	function __construct( array $repos = [], string $collection_identifier = 'FEAT' )
	{
		$this->repos = $repos;
		// setup some default Repo for a 'naked' run
		if ( empty( $this->repos ) ) {
			// the default FeaturesRepo for public Features of the user-websites
			$this->repos[] = [
				'Figuren_Theater\\FeaturesRepo' => WPMU_PLUGIN_DIR . '/Figuren_Theater/src/FeaturesRepo',
			];
		}
		$this->collection_identifier = $collection_identifier;

		$this->load_feature_repos();
	}

	protected function load_feature_repos()
	{
		if ( ! empty( $this->repos ) ) {
			array_map(function( $repo ){
				$this->load_feature_files( $repo );
			}, $this->repos );
		}
	}

	protected function load_feature_files( array $repo )
	{
		$_folder    = key(array_flip($repo));
		$_namespace = key($repo);

		foreach (
			new \RecursiveIteratorIterator( 
				new \RecursiveDirectoryIterator( $_folder ) ) as $SplFileInfo )
		{
			// filter out "." and ".."
			if ($SplFileInfo->isDir()) continue;

			// typical php require()
			if ( $this->require( $SplFileInfo->getRealPath() ) ) {
				// semi-autoload the Feature from the file
				// into our FeatureCollection
				$this->autoload_feature_into_collection( $SplFileInfo->getBasename('.php'), $_namespace );
			}
		}
	}


	protected function require($file) : bool
	{
		// cloned from Figuren_Theater\Psr4AutoloaderClass->requireFile()
		if (file_exists($file)) {
			require $file;
			return true;
		}
		return false;
	}

	protected function autoload_feature_into_collection( $feature_class, $namespace = __NAMESPACE__ )
	{

		$feature_class = $namespace.'\\'.$feature_class;

		\Figuren_Theater\API::get( $this->collection_identifier )->add( 
			$feature_class::SLUG, 
			new $feature_class
		);
	}
}
