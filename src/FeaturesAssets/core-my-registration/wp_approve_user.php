<?php 

namespace Figuren_Theater\FeaturesRepo\WP_Approve_User;

use Figuren_Theater\FeaturesRepo; // TEMP_USER_META

use WP_User;

use function add_action;
use function add_filter;
use function current_user_can;
use function esc_html__;
use function get_current_user_id;
use function remove_action;

/**
 * Renames the plugin's row actions to highlight, 
 * that not only a user will be approved, 
 * but also a new site will be created for this user;
 * if the user entered the needed data during register.
 *
 * @access public
 *
 * @param  array   $actions     User action links.
 * @param  WP_User $user_object User object.
 * @return array
 */
function user_row_actions( array $actions, WP_User $user_object ) : array {

	// Is the action available, we want to act on
	if ( ! isset( $actions['wpau-approve'] ) )
		return $actions;

	// Is the current user the one, of the current row? Otherwise leave.
	if ( get_current_user_id() === $user_object->ID )
		return $actions;

	// Is the current user allowed to edit the user of the actual row ?
	if ( ! current_user_can( 'edit_user', $user_object->ID ) )
		return $actions;

	// Did the user save any of the needed the data for the next step, during registration?
	if ( ! $_csw = $user_object->get( FeaturesRepo\TEMP_USER_META ) )
		return $actions;

	// Everything allright.
	// Rename the action-link.
	$actions['wpau-approve'] = str_replace(
		esc_html__( 'Approve', 'wp-approve-user' ),
		esc_html__( 'Approve and create site: ', 'wp-approve-user' ) 
			. '<kbd>' . $_csw['domain'] . '/' . $_csw['slug'] . '</kbd>',
		$actions['wpau-approve']
	);

	return $actions;
}
