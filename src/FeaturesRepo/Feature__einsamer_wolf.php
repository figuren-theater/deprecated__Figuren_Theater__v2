<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;
use Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\Network\Features;



class Feature__einsamer_wolf extends Features\Feature__Abstract
{

	const SLUG = 'einsamer-wolf';

	protected $supported_post_types = [];


	public function enable() : void 
	{
		\add_action( 'template_redirect', [$this, 'disable_author_archives'] );
		\add_action( 'init', [$this,'remove_caps'] );
		\add_action( 'init', [$this,'on__init'] );
	}


	public function on__init() : void 
	{
		// If in the admin, return.
		if ( is_admin() )
			return;

		array_map(
			function( String $post_type )
			{
				// removes 'Links' to the author-archives theme-independent
				\remove_post_type_support( $post_type, 'author' );
			},
			$this->get_supported_post_types()
		);
	}

	public function disable_author_archives() {

		if ( ! \is_author() )
			return;

		// Redirect author archives to the homepage 
		// with WordPress redirect function
		// set status to 301 permenant redirect. 
		// Function defaults to 302 temporary redirect. 
		\wp_safe_redirect( \get_home_url(), 301 );

		//That's all folks
		exit;
	}




	public function enable__on_admin() : void 
	{
		$this->remove_author_columns();
	}


	/**
	 * [remove_author_columns description]
	 *
	 * @todo MOVE INTO Admin_UI\Columns->remove()
	 * 
	 * @return [type] [description]
	 */
	public function remove_author_columns()
	{


		if (\current_user_can( 'manage_sites' ) )
			return;

		array_map(
			function( String $post_type )
			{
				\add_filter( 'manage_'.$post_type.'s_columns', function( $cols) {

					unset( $cols['author'] );
					return $cols;
				} );
			},
			$this->get_supported_post_types()
		);

	}


	public function remove_caps()
	{
		// disable "Add new user" from Admin-Bar
		$administrator = \get_role( 'administrator' );

		$caps = array(
			'edit_users',
			'create_users',
			'promote_users',
			'list_users',
		);

		foreach ( $caps as $cap ) {

			$administrator->remove_cap( $cap );
		}
	}


	protected function get_supported_post_types()
	{
		if ( !empty( $this->supported_post_types ))
			return $this->supported_post_types;

		return $this->supported_post_types = \get_post_types_by_support( 'author' );
	}

	/**
	 * Get a global menu index
	 *
	 * @since 0.1.0
	 *
	 * @param type $location
	 *
	 * @return mixed
	private function _wp_comment_humility_get_menu_index_by_slug( $location = '' ) {
		foreach ( $GLOBALS['menu'] as $index => $menu_item ) {
			if ( $location === $menu_item[2] ) {
				return $index;
			}
		}
		return false;
	}
	 */

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
