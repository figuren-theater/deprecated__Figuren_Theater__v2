<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;


interface Rule__will_add_admin_notice__Interface
{
	public function get_screen_id() : array;
	public function get_admin_notice() : Admin_Notice;
}
