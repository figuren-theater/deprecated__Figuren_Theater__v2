<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;



/**
 * Custom Registrationhandler for Taxonomies
 * 
 * provided by 'Extended CPT' Plugin,
 * which is loaded from /mu-plugins/_ft-vendor
 * 
 * This handler is used by our SiteManager for Taxonomies, 
 * in short our 'TaxonomiesManager',
 * to register all (own & custom) taxonomies.
 *
 * Its only job is registration and returning a proper Type as a result.
 */
class ExtendedCPT_TaxonomyRegisterer implements TaxonomyRegistration {
	
	public function register( Taxonomy__CanInitEarly__Interface $prepared_tax ) : \WP_Taxonomy {
		if ( function_exists( 'register_extended_taxonomy' ) ) {
			$_tax_object = \register_extended_taxonomy(
				$prepared_tax::NAME,
				$prepared_tax->post_types,
				$prepared_tax->args,
				$prepared_tax->labels
			);
		}

		if ( ! $_tax_object instanceof \WP_Taxonomy )
			$_tax_object = get_taxonomy( $prepared_tax::NAME );

		return $_tax_object;
	}
}

