<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater\inc\EventManager;


class Admin_Bar implements EventManager\SubscriberInterface
{


	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() : array
	{
		return array(
			// 
		);
	}




}

