<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Network\Features;

class Feature__open_infrastructure extends Features\Feature__Abstract {

	const SLUG = 'open-infrastructure';

	public function enable() : void {}
	
	public function enable__on_admin() : void
	{
		\add_action( 'tool_box', [$this, 'github_org_repos'] );
	}

	public function github_org_repos()
	{
		// allowed by github
		// https://github.com/logos
		$invertocat = 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png';
		?>
		<div class="card" style="background: #fff url('<?php echo $invertocat; ?>') 98% 5px no-repeat;background-size: auto 50px;">
			<h2 class="title"><?php _e( 'Github','figurentheater' ); ?></h2>
			<p>
			<?php
				printf(
					/* translators: %s: html link to github.com */
					__( 'The source-code of your website & the whole figuren.theater network is maintained at and deployed from %s, which is the major plattform for open-source projects on the web.','figurentheater' ),
					'<a href="https://github.com">github.com</a>'
				);
			?>
			</p>
			<p>
			<?php
				printf(
					/* translators: %s: html link to github.com/figuren-theater */
					__( 'Feel free to join our %s organization to stay in touch with your code and even the team. This organization holds 100 percent of the code running this website.','figurentheater' ),
					'<a href="https://github.com/figuren-theater">github.com/figuren-theater</a>'
				);
			?>
			</p>
			<a href="https://github.com/figuren-theater" class="button button-primary"><?php _e( 'Your source-code','figurentheater' ); ?></a>
		</div>
		<?php
	}

}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
