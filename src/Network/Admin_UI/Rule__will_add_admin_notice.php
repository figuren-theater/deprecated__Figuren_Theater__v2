<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;


/**
 * This UI Rule allows the showing of an typicall WP Admin-Notice
 * when the current user has the 'minimum capability' set
 * and we are viewing the admin page with the given 'screen_ID'.
 *
 * The actual 'rendering' is done by our 'AdminUIManager', 
 * who takes this Rule into its collection.
 */
class Rule__will_add_admin_notice extends Rule__Abstract implements Rule__will_add_admin_notice__Interface 
{

	/**
	 * The unique ID of the screen,
	 * we want our 'fields' to get highlighted.
	 *
	 * Will be compared to get_current_screen()->id
	 * 
	 * @var Array
	 */
	protected $screen_id = [];
	
	/**
	 * Admin_Notice obj with Message and CSS classes prepared
	 * @var Admin_Notice
	 */
	protected $admin_notice;

	function __construct( String $minimum_capability, $screen_id, Admin_Notice $admin_notice )
	{
		$this->minimum_capability = $minimum_capability;
		$this->screen_id          = (Array) $screen_id;
		$this->admin_notice       = $admin_notice;
	}

	/**
	 * Returns the screen_ID, 
	 * where this Rule should be implemented.
	 * 
	 * @return      String      The unique ID of the screen.
	 */
	public function get_screen_id() : array
	{
		// add the default and 'hidden' screen 
		// /wp-admin/options.php
		// with all options to the screen IDs to make this highlighting
		// available by default, for all Rules
		$this->screen_id[] = 'options';
		
		return $this->screen_id;
	}

	/**
	 * Returns the one Admin Notice to show for this particular screen_id
	 * @return Admin_Notice 
	 */
	public function get_admin_notice() : Admin_Notice
	{
		return $this->admin_notice;
	}

}
