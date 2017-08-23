<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'DWQA_QA_Component' ) ) :

class DWQA_QA_Component extends BP_Component {

	public function __construct() {
		parent::start(
			'dwqa',
			__( 'DWQA', 'dwqa' ),
			DWQA_DIR .'inc/extend/buddypress/'
		);
		$this->includes();
		$this->setup_globals();
		$this->setup_actions();
		$this->fully_loaded();
	}

	public function includes( $includes = array() ) {

		$includes[] = 'functions.php';

		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications.php';
		}

		// BuddyPress Group Extension class
		// if ( bbp_is_group_forums_active() && bp_is_active( 'groups' ) ) {
			// $includes[] = 'groups.php';
		// }

		parent::includes( $includes );
	}

	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary
		if ( !defined( 'BP_DWQA_SLUG' ) )
			define( 'BP_DWQA_SLUG', 'dwqa' );
			// define( 'BP_DWQA_SLUG', $this->id );

		$args = array(
			'path'          => BP_PLUGIN_DIR,
			'slug'          => BP_DWQA_SLUG,
			'root_slug'     => BP_DWQA_SLUG,
			'has_directory' => false,
			'search_string' => __( 'Search DWQA...', 'dwqa' ),
		);

		parent::setup_globals( $args );
	}

	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !bp_displayed_user_id() )
			return;

		// Define local variable(s)
		$user_domain = '';

		// Add 'DWQA' to the main navigation
		$main_nav = array(
			'name'                => __( 'DWQA', 'dwqa' ),
			'slug'                => $this->slug,
			'position'            => 80,
			'screen_function'     => 'dp_dwqa_screen_questions',
			'default_subnav_slug' => 'dwqa-question',
			'item_css_id'         => $this->id
		);

		// Determine user to use
		if ( bp_displayed_user_id() )
			$user_domain = bp_displayed_user_domain();
		elseif ( bp_loggedin_user_domain() )
			$user_domain = bp_loggedin_user_domain();
		else
			return;

		// User link
		$dwqa_link = trailingslashit( $user_domain . $this->slug );

		$sub_nav[] = array(
			'name'            => __( 'Questions', 'dwqa' ),
			'slug'            => 'dwqa-question',
			'parent_url'      => $dwqa_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'dp_dwqa_screen_questions',
			'position'        => 20,
			'item_css_id'     => 'topics'
		);

		/*
		$sub_nav[] = array(
			'name'            => __( 'Answers', 'dwqa' ),
			'slug'            => 'dwqa-answer',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'dp_dwqa_screen_answers',
			'position'        => 40,
			'item_css_id'     => 'replies'
		);

		$sub_nav[] = array(
			'name'            => __( 'Comments', 'dwqa' ),
			'slug'            => 'dwqa-comment',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'dp_dwqa_screen_comments',
			'position'        => 60,
			'item_css_id'     => 'favorites'
		); */

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the admin bar
	 *
	 * @since bbPress (r3552)
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		if ( !bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) )
			return;
		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables
			$user_domain = bp_loggedin_user_domain();
			$dwqa_link = trailingslashit( $user_domain . $this->slug );

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'DWQA', 'dwqa' ),
				'href'   => trailingslashit( $dwqa_link )
			);
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id.'-question',
				'title'  => __( 'Questions', 'dwqa' ),
				'href'   => trailingslashit( $dwqa_link )
			);
		 
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @since bbPress (r3552)
	 */
	public function setup_title() {
		$bp = buddypress();

		// Adjust title based on view
		if ( bp_is_forums_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Forums', 'bbpress' );
			} elseif ( bp_is_user() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb'
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}
	
	public function setup_actions() {

		// Setup the components
		add_action( 'bp_init', array( $this, 'setup_components' ), 7 );

		parent::setup_actions();
	}
	public function setup_components() {

		// Always load the members component
		bbpress()->extend->buddypress->members = new BBP_BuddyPress_Members;

		/* // Create new activity class
		if ( bp_is_active( 'activity' ) ) {
			bbpress()->extend->buddypress->activity = new BBP_BuddyPress_Activity;
		} */

		/* // Register the group extension only if groups are active
		if ( bbp_is_group_forums_active() && bp_is_active( 'groups' ) ) {
			bp_register_group_extension( 'BBP_Forums_Group_Extension' );
		} */
	}
	
	private function fully_loaded() {
		do_action_ref_array( 'bp_dwqa_buddypress_loaded', array( $this ) );
	}
}
endif;
