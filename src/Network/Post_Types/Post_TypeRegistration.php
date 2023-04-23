<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;



interface Post_TypeRegistration
{
	public function register( Post_Type__CanInitEarly__Interface $prepared_post_type ) : \WP_Post_Type;
}
