<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Post_Types;



/**
 * Custom Registrationhandler for Post_Types
 * 
 * provided by 'Extended CPT' Plugin,
 * which is loaded from /mu-plugins/_ft-vendor
 * 
 * This handler is used by our SiteManager for Post_Types, 
 * in short our 'Post_TypesManager',
 * to register all (own & custom) taxonomies.
 *
 * Its only job is registration and returning a proper Type as a result.
 */
class ExtendedCPT_Post_TypeRegisterer implements Post_TypeRegistration {
	
	public function register( Post_Type__CanInitEarly__Interface $prepared_post_type) : \WP_Post_Type {
		if ( function_exists( 'register_extended_post_type' ) ) {
			$_pt_object = \register_extended_post_type(
				$prepared_post_type::NAME,
				$prepared_post_type->args,
				$prepared_post_type->labels
			);
		}

		if ( ! $_pt_object instanceof \WP_Post_Type )
			$_pt_object = get_post_type_object( $prepared_post_type::NAME );

		return $_pt_object;
	}
}
