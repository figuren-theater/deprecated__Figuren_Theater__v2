<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;


interface Post_Type__CanInitEarly__Interface
{
	public function init_post_type() : void;
}
