<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\inc\EventManager as Inc;



class Highlight_Settings_Fields implements Inc\SubscriberInterface
{


	protected $current_screen = null; 

	/**
	 * 'action'-property
	 * 
	 * @var array
	 */
	protected $highlight_settings = [];

	protected $css = '';
	protected $html_attr = '';
	protected $css_class = '';


	function __construct( array $highlight_settings, String $html_attr = 'readonly', String $css_class = 'ft_admin_readonly', String $css = '' )
	{
		$this->highlight_settings = $highlight_settings;

		$this->html_attr = $html_attr;
		$this->css_class = $css_class;
	
	    // prepare default CSS for .ft_admin_readonly Class,
	    // which should not be present in the fn call
	    // for better readability, so ...
	    $this->css = ('' !== $css && is_string($css)) ? $css : 'border-color:var(--wp-admin-theme-color-darker-10,rgba(210,3,148,.5))!important;background-color:#eee!important;pointer-events: none;touch-action: none;';
	}

    /**
     * Returns an array of hooks that this subscriber wants to register with
     * the WordPress plugin API.
     *
     * @return array
     */
    public static function get_subscribed_events() : array
    {
        return array(
            'admin_print_footer_scripts' => 'highlight_fields',
        );
    }


	/**
	 * Get the current screen object
	 *
	 * Some additional notes [...] for implementing get_current_screen() functionality, 
	 * the WP_Screen object returned will be null if called from the 'admin_init' hook.
	 * 
	 * @return    WP_Screen|null    Current screen object or null when screen not defined.
	 *                              Core class used to implement an admin screen API.
	 */
	protected function get_current_screen()
	{
		// minimal caching
		if (null !== $this->current_screen)
			return $this->current_screen;

		return $this->current_screen = \get_current_screen();
	}



	/**
	 * Mark input fields as read-only (e.g.)
	 *
	 * Here will all 'Rule__will_highlight_settings' Rules being rendered.
	 *
	 * @todo Make this a nice new highlight_fields( [ screen_id => [[option_names]] ]) 
	 *       but at the moment I'm 'using' it alone, so go for it ....
	 *       
	 */
	public function highlight_fields()
	{
	    // 
	    if ( empty( $this->highlight_settings ))
	    	return;

	    // try better without using 'in_array'
	    if ( ! isset( $this->highlight_settings[ $this->get_current_screen()->id ] ) ) 
	    	return;

#		$option_names = wp_list_pluck( $this->collection->get(), 'name' );
		$option_names = \array_values( 
			\array_merge([],...$this->highlight_settings[ $this->get_current_screen()->id ])
		);
#		$option_names = $this->highlight_settings[ $this->get_current_screen()->id ];
/*
wp_die('<pre>'.var_export(array(
	$option_names,
	array_merge(...$this->highlight_settings[ $this->get_current_screen()->id ]),
	array_merge([],...$this->highlight_settings[ $this->get_current_screen()->id ]),
)).'</pre>');*/


		// CSS ID selectors
		$_by_id = '#'.implode(',#', $option_names);
		// CSS name selector
		$_by_name  = '[name=\''.implode('[]\'],[name=\'', $option_names).'[]\']';
		$_by_name .= ',[name=\''.implode('\'],[name=\'', $option_names).'\']';
		//
		$_by_custom_id = '#'.implode('_custom,#', $option_names).'_custom';


		echo "<script type='text/javascript'>\n";
		echo "jQuery(document).ready(function($) {\n";
#		echo '$("'.$_by_id.','.$_by_name.','.$_by_custom_id.'").attr("disabled", "disabled");';
#		//adding the disabled attribute won't send the values with post
		echo '$("'.$_by_id.','.$_by_name.','.$_by_custom_id.'").prop( "'.$this->html_attr.'","'.$this->html_attr.'").addClass("'.$this->css_class.'");';
		echo "\n});\n";
		echo "</script>\n";
		echo '<style>.'.$this->css_class.'{'.$this->css.'}</style>';

	}



}
