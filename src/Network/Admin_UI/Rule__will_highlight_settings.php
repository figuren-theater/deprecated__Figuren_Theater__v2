<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;


/**
 * This UI Rule allows the highlighting of 'fields'
 * when the current user has the 'minimum capability' set
 * and we are viewing the admin page with the given 'screen_ID'.
 *
 * 'Fields' in this context could be anything, that could be touched by jQuery,
 * which handles the actual highlighting.
 * 
 * The actual 'rendering' is done by our 'AdminUIManager', 
 * who takes this Rule into its collection.
 */
 class Rule__will_highlight_settings extends Rule__Abstract implements Rule__will_highlight_settings__Interface 
{

	/**
	 * The unique ID of the screen,
	 * we want our 'fields' to get highlighted.
	 *
	 * Will be compared to get_current_screen()->id
	 * 
	 * @var string
	 */
	protected $screen_id = '';
	
	/**
	 * List of options-names, to which the fields should be manipulated.
	 * For compatibility with many different cases how Plugin-Authors wrote 
	 * their options-pages and named their input fields, 
	 * we go for fuzzy matching the 'field's id- and name-Attributes based on the options-names.
	 *
	 * That said, a list with the following options-names ...
	 * ['my_famous_option_1','my_famous_option_2']
	 *
	 * ... will get transformed into CSS selectors used by jQuery like so:
	 * $("
	 *    #my_famous_option_1, [name='my_famous_option_1'], [name='my_famous_option_1[]'], #my_famous_option_1_custom,
	 *    #my_famous_option_2, [name='my_famous_option_2'], [name='my_famous_option_2[]'], #my_famous_option_2_custom,
	 *  ")
	 * @var array
	 */
	protected $highlight_settings = [];

	function __construct( String $minimum_capability, $screen_id, Array $highlight_settings )
	{
		$this->minimum_capability = $minimum_capability;
		$this->screen_id          = (Array) $screen_id;
		$this->highlight_settings = $highlight_settings;
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
	 * Returns list of option-names,
	 * from which to create fuzzy-matching CSS selectors.
	 * @return Array e.g. "['my_famous_option_1','my_famous_option_2']"
	 */
	public function get_highlight_settings() : array
	{
		return $this->highlight_settings;
	}

}
