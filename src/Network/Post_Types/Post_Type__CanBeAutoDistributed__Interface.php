<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;

use Distributor\InternalConnections as Connections;


interface Post_Type__CanBeAutoDistributed__Interface
{

	/**
	 * Called on post_publish
	 * Filter documented in distributor/includes/classes/ExternalConnections/WordPressExternalConnection.php

	 */
	public function on_auto_distribute( array $new_post_args, \WP_Post $post, array $args, Connections\NetworkSiteConnection $connection ) : array;

}
