<?php
/**
 * This file contains the
 * interface for post_types and taxonomies,
 * which are able to set 'wpseo_titles' sub-options
 *
 * @package FT_PROTOTYPE_TITLE_TAG_MANAGER
 * @version 2022.04.15
 * @author  Carsten Bach
 */

declare(strict_types=1);

namespace Figuren_Theater\SiteParts;

interface Data__CanAddYoastVariables__Interface {
	public static function get_wpseo_variables() : array;
}
