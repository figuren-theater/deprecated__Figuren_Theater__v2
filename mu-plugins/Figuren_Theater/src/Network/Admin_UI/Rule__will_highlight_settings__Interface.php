<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;


interface Rule__will_highlight_settings__Interface
{
	public function get_screen_id() : array;
	public function get_highlight_settings() : array;
}
