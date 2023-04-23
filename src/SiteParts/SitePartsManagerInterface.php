<?php
declare(strict_types=1);

namespace Figuren_Theater\SiteParts;

/**
 * Because SitePartManagers do the heavy work.
 *
 * We use this method to remind ourselves to properly start the work.
 * This is more a design-decission, than actually needed by the code.
 */
interface SitePartsManagerInterface
{
	public function init() : void;
}
