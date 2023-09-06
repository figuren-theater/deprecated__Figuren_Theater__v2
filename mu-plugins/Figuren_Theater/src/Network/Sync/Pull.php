<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Sync;
// use Figuren_Theater\FeaturesRepo;
// use Figuren_Theater\Network\Post_Types;

use Distributor\InternalConnections;

/**
 * (Manually|Programmatically) Distribute some post on sudden conditions using the glorious
 * 'Distributor' Plugin by 10up
 * @example 
 * ```
 *  // 1. Defining the Pull
 *  $distributor = new Sync\Pull( 
 *  	[ 25 ],         // the ID of the $post you want to pull ON THE REMOTE SITE
 *  	1,              // the site|blog_id of the remote site you want to pull from
 *  	'page'          // the post_type our $post belongs to
 *  	'draft|publish' // the post_status we want the pulled $post to become
 *  );
 *  // 2. Running the Pull
 *  $distributor->run();
 * ```		
 *
 */
class Pull
{
	function __construct( array $array_of_ids, int $remote_site_id, String $post_type, String $post_status = 'publish' )
	{

		// last checking
		$this->remote_site = \get_site( $remote_site_id );
		if ( ! $this->remote_site instanceof \WP_Site )
		{
			\do_action( 'qm/warning', 'The Site with ID "{id}", you want to pull from, doesn\'t exist.', [
			    'id' => $remote_site_id,
			] );
			return;
		}

		// from  content\plugins\distributor\includes\pull-ui.php#L225
		$this->posts = array_map(
			function( $remote_post_id ) use ( $post_type, $post_status ) {
					return [
						'remote_post_id' => $remote_post_id,
						'post_type'      => $post_type,
						'post_status'    => $post_status,
					];
			},
			$array_of_ids
		);
	}

	public function run()
	{
		// from  content\plugins\distributor\includes\pull-ui.php#L249
		$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $this->remote_site );
		$new_posts  = $connection->pull( $this->posts );

		return $new_posts;
	}
}
