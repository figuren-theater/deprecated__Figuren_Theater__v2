<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;
use Figuren_Theater\inc\EventManager as Inc;



class Render_Admin_Notice implements Inc\SubscriberInterface
{


	protected $current_screen = null; 

	/**
	 * 'action'-property
	 * 
	 * @var array
	 */
	protected $admin_notice = [];


	function __construct( array $add_admin_notice )
	{
		$this->admin_notice = $add_admin_notice;
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
			'all_admin_notices' => 'add_admin_notices',
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
	 * Display admin notices 
	 *
	 * can handle multiple notices for the same screen_id
	 */
	public function add_admin_notices()
	{

		// 
		if ( empty( $this->admin_notice ))
			return;

		// try better without using 'in_array'
		if ( ! isset( $this->admin_notice[ $this->get_current_screen()->id ] ) ) 
			return;

		foreach ($this->admin_notice[ $this->get_current_screen()->id ] as $admin_notice) {
			echo $admin_notice->output();
		}

	}


}
