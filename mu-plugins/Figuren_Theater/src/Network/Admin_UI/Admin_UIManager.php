<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;

use Figuren_Theater\Network\Admin_UI\Interfaces as Admin_UI_Interfaces;
use Figuren_Theater\SiteParts;

use Figuren_Theater\inc\EventManager;


/**
 * Fundament of all SitePartManager classes.
 * SiteParts (in our situation) are
 * all the elements of our WordPress Site,
 * that we maybe want to change in certain situations.
 *
 * Theese SiteParts will be especially
 *  -- Plugins
 *  -- Options
 *  -- Taxonomies
 *  -- Post_Types
 *  -- RewriteRules
 *  -- UserRoles
 *  -- etc. ... (will be continued)
 */
class Admin_UIManager extends SiteParts\SitePartsManagerAbstract implements EventManager\SubscriberInterface {
	/**
	 * 'action'-property
	 * 
	 * @var array
	 */
	public $remove_menus = [];

	/**
	 * 'action'-property
	 * 
	 * @var array
	 */
	public $add_admin_notice = [];

	/**
	 * 'action'-property
	 * 
	 * @var array
	 */
	public $highlight_settings = [];


	/**
	 * Returns an array of hooks that this subscriber wants to register with
	 * the WordPress plugin API.
	 *
	 * Load Order Admin: 
	 * init -> widgets_init -> wp_loaded -> admin_menu -> admin_init
	 * 
	 * That's why we take 'admin_menu', it's the 1st relevant action to hook on, 
	 * for admin-only stuff, without triggering during request on admin-ajax.php,
	 * which is used from the frontend also
	 * 
	 * So, Load everything 
	 * from the collection into prepared properties,
	 * so that our prepared methods can act on this.
	 * 
	 * @return array
	 */
	public static function get_subscribed_events() : array {
		return [
			/**
			 * Fires before the administration menu loads in the Network Admin.
			 */
			'network_admin_menu' => [ 'init', 2 ],
			/**
			 * Fires before the administration menu loads in the User Admin.
			 */
			'user_admin_menu'    => [ 'init', 2 ],
			/**
			 * Fires before the administration menu loads in the admin.
			 */
			'admin_menu'         => [ 'init', 2 ],
		];
	}


	/**
	 * Init our Manager onto WordPress 
	 * 
	 * This could mean 'register_something', 
	 * 'add_filter_to_soemthing' or anything else,
	 * to do (probably on each SitePart inside the collection).
	 *
	 * This should be hooked into WP.
	 */
	public function init() : void {

		array_map( [ $this, 'can_implement_rule' ], $this->collection->get() );

		array_map( 
			[ \Figuren_Theater\FT::site()->EventManager, 'add_subscriber' ],
			[
				new Remove_Menus( $this->remove_menus ),
				new Render_Admin_Notice( $this->add_admin_notice ),
				new Highlight_Settings_Fields( $this->highlight_settings ),
				new Color_Scheme(),
				new User_List(),
				// new Admin_Bar(),
				new Admin_Footer(),
				// new Site_Health_Checks(),
				new Welcome_Panel(),
			]
		);
	}


	/**
	 * Find out, what UI manipulation to load 
	 * from the currently attached Rules.
	 *
	 * We do this here and late,
	 * therefore that we can throw UI/Rules to the collection 
	 * at anytime and from anywhere, but before this method is called, 
	 * without worrying about 'current_user_can' or 'get_current_screen'.
	 *
	 * We ask the Rule if their major requirements are met,
	 * with the 'can_implement' on the rule itself.
	 *
	 * Then we send our Admin_UIManager to the Rule,
	 * what will append their 'action'-properties to our 'action'-properties.
	 * 
	 * @param  Rule__Interface $rule [description]
	 */
	public function can_implement_rule( Rule__Interface $rule ) : void {
		if ( $rule->can_implement() ) {
			$rule->with_cap( $this );
		} else {
			$rule->without_cap( $this );
		}
	}


	public function collect_rules( Admin_UI_Interfaces\Has_Rule $component ) : void {

		if ( $component instanceof Admin_UI_Interfaces\Has_Menu_Rule ) {

			$this->collection->add( 
				$component::BASENAME . '__remove_menus', 
				new Rule__will_remove_menus( 
					$component->get_menus_to_remove_cap(),
					$component->get_menus_to_remove()
				)
			);

		}

		if ( $component instanceof Admin_UI_Interfaces\Has_Notice_Rule ) {
			// helper
			$admin_ui_manager = $this;

			// get notices or set empty array to remove all
			$notices = ( ! empty( $component->get_notices() ) ) ? $component->get_notices() : [];
			
			array_walk( 
				$notices, 
				function( Admin_Notice $notice ) use ( $admin_ui_manager, $component ) {

					$admin_ui_manager->collection->add( 
						$component::BASENAME . '__admin_notice', 
						new Rule__will_add_admin_notice( 
							$component->get_needed_cap(),
							$component->get_related_screen_ids(),
							$notice
						)
					);
				}
			);
		}
	}

}



\add_action( 
	'Figuren_Theater\init', 
	function ( $ft_site ) : void {

		if ( ! is_a( $ft_site, 'Figuren_Theater\ProxiedSite' ))
			return;

		// 7. Setup all Admin_UI as part of our Collection
		// 7.1. Create Collection 
		// It's important, to do that before ADDing post_types,
		// to properly instantiate our collection.
		$collection = Admin_UICollection::get_collection();

		// 7.2. Add all Admin_UI to the collection
		//      
		// This is done from inside /Figuren_Theater/Admin_UI NOPE
		// We have to call from here to make our autoloader aware of the classes NOPE
		// We use an Semi-Autoloader on the Admin_UIRepo Folder
		// new Admin_UIRepo\Bootstrap_Admin_UIRepo;

		// 7.3. Setup SitePart Manager for 'Admin_UI'
		// with our prepared Collection
		$ft_site->set_Admin_UIManager( 
			// with its Admin_UICollection
			new Admin_UIManager( $collection )
		);
	},
	80
);
