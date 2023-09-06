<?php

declare(strict_types=1);

namespace Figuren_Theater;

// Static Proxy.
final class FT {
	public static function site(): ProxiedSite {

		// Kept for easier abstraction
		static $site = null;

		if ( null === $site ) {
			// You can have arbitrary logic in here to decide what
			// implementation to use.
			$site = new ProxiedSite();
		}

		return $site;
	}
}
