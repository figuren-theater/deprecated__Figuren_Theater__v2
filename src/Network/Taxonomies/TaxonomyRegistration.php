<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Taxonomies;




interface TaxonomyRegistration
{
	public function register( Taxonomy__CanInitEarly__Interface $prepared_tax ) : \WP_Taxonomy;
}
