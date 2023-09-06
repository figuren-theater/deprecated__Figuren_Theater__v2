<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\SiteParts as SiteParts;


/**
 * A UI Rule is what triggers a UI change
 * compared to default WP or WP-(some-plugin) behavior.
 *
 * It's a simple 'if else' on a meta_cap
 */
abstract class Rule__Abstract implements Rule__Interface 
{
	
	protected $minimum_capability;

	function __construct( String $minimum_capability )
	{
		$this->minimum_capability = $minimum_capability;
	}

	/**
	 * Returns minimum capability to implement this Rule.
	 * @return          String        WP_capability
	 */
	public function get_minimum_capability() : string
	{
		return $this->minimum_capability;
	}

	/**
	 * Checks what this Rule needs to meet all requirements.
	 * By default, this only checks the 'minimum_capability'
	 * for the current user.
	 *
	 * But this could be overwritten by an extend to check for the Screen_ID e.g.
	 * 
	 * @return bool     wether or not to implement this rule
	 */
	public function can_implement() : bool
	{
		return current_user_can( $this->get_minimum_capability() );
	}



	/**
	 * Method that will be called by the Admin_UIManager
	 * when the needed 'minimum_capability' 
	 * is not met for the current user.
	 * 
	 * @return   none
	 */
	public function without_cap( SiteParts\SitePartsManagerInterface $Admin_UIManager ) : void
	{
		if ( $this instanceof Rule__will_remove_menus__Interface ) {
			// typically we want to remove some menu, when user has not the capability,
			// but by making 'without_cap' a public function we can extend|change this in a later case.
		
			// $Admin_UIManager->remove_menus[] = $this->remove_menus();
			foreach ($this->remove_menus() as $key => $menu) {
				$Admin_UIManager->remove_menus[] = [$key => $menu];
			}
		}
	}


	/**
	 * Method that will be called by the Admin_UIManager
	 * when the needed 'minimum_capability' 
	 * is met for the current user.
	 * 
	 * @return   none
	 */
	public function with_cap( SiteParts\SitePartsManagerInterface $Admin_UIManager ) : void
	{
		// PSEUDOCODE
		// THIS->add_admin_notice
		if ( $this instanceof Rule__will_add_admin_notice__Interface ) {
			// allow multiple screen_ids with the same Admin_NOtice
			array_map(
				function( $screen_id ) use ( $Admin_UIManager ) {
					$Admin_UIManager->add_admin_notice[ $screen_id ][] = $this->get_admin_notice();
				},
				$this->get_screen_id()
			);
		}

		// THIS->highlight_settings_field
		if ( $this instanceof Rule__will_highlight_settings__Interface ) {
			// allow multiple screen_ids with the same highlighted settings
			array_map(
				function( $screen_id ) use ( $Admin_UIManager ) {
					$Admin_UIManager->highlight_settings[ $screen_id ][] = $this->get_highlight_settings();
				},
				$this->get_screen_id()
			);
		}
	}

}
