<?php
declare(strict_types=1);

namespace Figuren_Theater\FeaturesRepo;

use Figuren_Theater\Coresites\Post_Types;
use Figuren_Theater\Network\Features;



class Feature__sichere_emails extends Features\Feature__Abstract
{

	const SLUG = 'sichere-emails';


	public function enable() : void {}
	
	public function enable__on_admin() : void
	{
		\add_action( 'tool_box', [$this, 'jpberlin_webmail'] );
		\add_action( 'tool_box', [$this, 'jpberlin_verwaltung'] );
	}

	public function jpberlin_webmail()
	{
		?>
		<div class="card" style="background: #fff url('https://webmail.jpberlin.de/roundcube/jpberlin142x42.png') 98% 5px no-repeat">
			<h2 class="title"><?php _e( 'Webmail','figurentheater' ); ?></h2>
			<p>
			<?php
				printf(
					/* translators: %s: URL to Import screen. */
					__( 'Manage your Inbox from everywhere using your Webmail Interface powered by our extraordinary hosting-partner <a href="%s"><em>JPBerlin</em></a>.','figurentheater' ),
					'https://jpberlin.de/'
				);
			?>
			</p>
			<a href="https://webmail.jpberlin.de/" class="button button-primary"><?php _e( 'Webmail','figurentheater' ); ?></a>&nbsp;
			<a href="<?php echo static::get_feature_post_url(); ?>" class="button button-secondary"><?php _e( 'Read about the Details','figurentheater' ); ?></a>
		</div>
		<?php
	}

	public function jpberlin_verwaltung()
	{
		?>
		<div class="card" style="background: #fff url('https://webmail.jpberlin.de/roundcube/jpberlin142x42.png') 98% 5px no-repeat">
			<h2 class="title"><?php _e( 'Email Management','figurentheater' ); ?></h2>
			<p>
			<?php
				printf(
					/* translators: %s: URL to Import screen. */
					__( 'Manage your Email-Adresses from everywhere using your Management Interface powered by our extraordinary hosting-partner <a href="%s"><em>JPBerlin</em></a>.','figurentheater' ),
					'https://jpberlin.de/'
				);
			?>
			</p>
			<a href="https://verwaltung.jpberlin.de/" class="button button-primary"><?php _e( 'Email Management','figurentheater' ); ?></a>
		</div>
		<?php
	}

	public static function get_feature_post_url() {
		
		$coresites = array_flip( FT_CORESITES );
		
		return \get_site_url(
			$coresites['webs'],
			'/' . Post_Types\Post_Type__ft_feature::SLUG . '/' . static::SLUG . '/',
			'https'
		);
	}



}
// NO NEED TO CALL THIS CLASS DIRECTLY
// our 'Bootstrap_FeaturesRepo' Class takes care of it
// as long as this file lives in the \FeaturesRepo ;)
