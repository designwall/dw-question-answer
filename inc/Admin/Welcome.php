<?php

class DWQA_Admin_Welcome {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'welcome' ) );
	}

	public function welcome() {
		$activated = get_option( 'dwqa_plugin_activated', false );
		if ( $activated ) {
			delete_option( 'dwqa_plugin_activated' );
			wp_safe_redirect( esc_url( add_query_arg( array( 'page' => 'dwqa-about' ), admin_url( 'index.php' ) ) ) );
			exit;
		}
	}

	public function admin_menus() {
		add_dashboard_page(
			__( 'Welcome to DW Question & Answer', 'dwqa' ),
			__( 'Welcome to DW Question & Answer', 'dwqa' ),
			'manage_options',
			'dwqa-about',
			array( $this, 'about_layout' )
		);

		add_dashboard_page(
			__( 'DW Question & Answer Changelog', 'dwqa' ),
			__( 'DW Question & Answer Changelog', 'dwqa' ),
			'manage_options',
			'dwqa-changelog',
			array( $this, 'changelog_layout' )
		);

		add_dashboard_page(
			__( 'DW Question & Answer Credits', 'dwqa' ),
			__( 'DW Question & Answer Credits', 'dwqa' ),
			'manage_options',
			'dwqa-credits',
			array( $this, 'credits_layout' )
		);
	}

	public function admin_init() {
		remove_submenu_page( 'index.php', 'dwqa-about' );
		remove_submenu_page( 'index.php', 'dwqa-changelog' );
		remove_submenu_page( 'index.php', 'dwqa-credits' );
	}

	public function page_head() {
		global $dwqa;
		?>
		<h1><?php printf( __( 'Welcome to DW Question & Answer %s', 'dwqa' ), $dwqa->version ) ?></h1>
		<p class="about-text"><?php _e( 'Thank you for installing our WordPress plugin. If you have any question about this theme, please submit to our <a target="_blank" href="https://www.designwall.com/question/">Q&A section</a>.', 'dwqa' ); ?></p>
		<?php
	}

	public function tabs() {
		$current_tab = isset( $_GET['page'] ) ? $_GET['page'] : 'dwqa-about'; 
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'dwqa-about' ), admin_url( 'index.php' ) ) ) ?>" class="nav-tab <?php echo 'dwqa-about' == $current_tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'What&#8217;s New' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'dwqa-changelog' ), admin_url( 'index.php' ) ) ) ?>" class="nav-tab <?php echo 'dwqa-changelog' == $current_tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'Changelog' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'dwqa-credits' ), admin_url( 'index.php' ) ) ) ?>" class="nav-tab <?php echo 'dwqa-credits' == $current_tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'Credits' ); ?></a>
		</h2>
		<?php
	}

	public function about_layout() {
		global $dwqa;
		?>
		<div class="wrap about-wrap">
			<?php $this->page_head(); ?>
			<?php $this->tabs(); ?>
			<p class="about-description">In recent weeks, we have been working hard to enhance our DW Question &#38; Answer (DW Q&#38;A) plugin. Apart from getting bugs fixed and providing technical assistance to our users, our team implemented some new cool features to extend functionality for DW Q&#38;A. For more info, see <a href="https://www.designwall.com/blog/hi-2016-dw-question-answer-plugin-is-back-with-4-new-extensions/">instruction</a>.</p>

			
			<div class="feature-section two-col">
				<div class="col">
					<div class="media-container">
						<img src="<?php echo esc_url( $dwqa->uri . 'assets/img/dw-markdown.png' ) ?>" sizes="(max-width: 500px) calc(100vw - 40px), (max-width: 782px) calc(100vw - 70px), (max-width: 960px) calc((100vw - 116px) * .476), (max-width: 1290px) calc((100vw - 240px) * .476), 500px">
					</div>
				</div>
				<div class="col">
					<h3><?php _e( 'DWQA Markdown', 'dwqa' ) ?></h3>
					<p>Markdown is an extremely popular markup language supported by many platforms which provides an easy way to style text without learning a lot of complicated codes and shortcuts.</p>
					<a target="_blank" class="button button-primary" href="http://bit.ly/dwqa-markdown"><?php _e( 'Get It Now!', 'dwqa' ) ?></a>
				</div>
			</div>
			<hr>
			<div class="feature-section two-col">
				<div class="col">
					<div class="media-container">
						<img src="<?php echo esc_url( $dwqa->uri . 'assets/img/dw-leaderboard.png' ) ?>" sizes="(max-width: 500px) calc(100vw - 40px), (max-width: 782px) calc(100vw - 70px), (max-width: 960px) calc((100vw - 116px) * .476), (max-width: 1290px) calc((100vw - 240px) * .476), 500px">
					</div>
				</div>
				<div class="col">
					<h3><?php _e( 'DWQA Leaderboard', 'dwqa' ) ?></h3>
					<p>DWQA Leaderboard is a simple (premium) WordPress add-on for the DW Question &#38; Answer plugin. It allows you to create a list of users who have made great contributions to your community across a period of time (this week/month, last week/month, all time etc.,). You can create multiple instances of DWQA Leaderboard and easily assign to the sidebar via widget dashboard.</p>
					<a target="_blank" class="button button-primary" href="http://bit.ly/dwqa-leaderboard"><?php _e( 'Get It Now!', 'dwqa' ) ?></a>
				</div>
			</div>
			<hr>
			<div class="feature-section two-col">
				<div class="col">
					<div class="media-container">
						<img src="<?php echo esc_url( $dwqa->uri . 'assets/img/dw-captcha.png' ) ?>" sizes="(max-width: 500px) calc(100vw - 40px), (max-width: 782px) calc(100vw - 70px), (max-width: 960px) calc((100vw - 116px) * .476), (max-width: 1290px) calc((100vw - 240px) * .476), 500px">
					</div>
				</div>
				<div class="col">
					<h3><?php _e( 'DWQA Captcha', 'dwqa' ) ?></h3>
					<p>Using our DWQA CAPTCHA form on your comments may be slightly tedious for you commenters’, but using it can eliminate a large chunk of those spam questions and comments from even happening in the first place. This means that you can spend more time on the good bits of running a WordPress site, and less on comment moderation — we all hate that part.</p>
					<p>Supporting google reCaptcha version 2 and FunCaptcha.</p>
					<a target="_blank" class="button button-primary" href="http://bit.ly/dwqa-captcha"><?php _e( 'Get It Now!', 'dwqa' ) ?></a>
				</div>
			</div>
			<hr>
			<div class="feature-section two-col">
				<div class="col">
					<div class="media-container">
						<img src="<?php echo esc_url( $dwqa->uri . 'assets/img/dw-embedquestion.png' ) ?>" sizes="(max-width: 500px) calc(100vw - 40px), (max-width: 782px) calc(100vw - 70px), (max-width: 960px) calc((100vw - 116px) * .476), (max-width: 1290px) calc((100vw - 240px) * .476), 500px">
					</div>
				</div>
				<div class="col">
					<h3><?php _e( 'DWQA Embed Question', 'dwqa' ) ?></h3>
					<p>DWQA Embed Question is a WordPress embed plugin (or an add-on) for our WordPress Q&#38;A DW Question and Answer plugin. This plugin helps you to embed a question content from DW Question and Answer site into your post, blog, widgets or any other site.</p>
					<a target="_blank" class="button button-primary" href="http://bit.ly/dwqa-embed-questions"><?php _e( 'Get It Now!', 'dwqa' ) ?></a>
				</div>
			</div>
			<hr>
			<div class="headline-feature feature-video">
				<iframe width="1050" height="591" src="https://www.youtube.com/embed/usS9ug0pI7A" frameborder="0" allowfullscreen></iframe>
			</div>
		</div>
		<?php
	}

	public function changelog_layout() {
		global $dwqa;
		?>
		<div class="wrap about-wrap">
			<?php $this->page_head(); ?>
			<?php $this->tabs(); ?>

			<div class="changelog">
				<p><?php echo $this->parse_readme(); ?></p>
			</div>
		</div>
		<?php
	}

	public function credits_layout() {
		$contributors = $this->get_contributors();
		?>
		<div class="wrap about-wrap">
			<?php $this->page_head(); ?>
			<?php $this->tabs(); ?>

			<ul class="wp-people-group" id="wp-people-group-project-leaders">
			<?php if ( !empty( $contributors ) ) : ?>
				<h3 class="wp-people-group"><?php _e( 'Contributors', 'dwqa' ); ?></h3>
				<?php foreach( $contributors as $contributor ) : ?>
					<li class="wp-person" id="wp-person-nacin">
						<a href="<?php echo esc_url( $contributor->url ) ?>">
							<img width="60" height="60" src="<?php echo esc_url( $contributor->avatar_url ) ?>" class="gravatar" alt="<?php echo esc_html( $contributor->login ) ?>">
						</a>
						<a href="<?php echo esc_url( $contributor->url ) ?>"><?php echo esc_html( $contributor->login ) ?></a>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
			</ul>
		</div>
		<?php
	}

	public function parse_readme() {
		$file = file_exists( DWQA_DIR . 'readme.txt' ) ? DWQA_DIR . 'readme.txt' : false;

		if ( !$file ) {
			$readme = '<p>' . __( 'No valid changelog was found.', 'dwqa' ) . '</p>';
		} else {
			$readme = file_get_contents( $file );
			$readme = nl2br( esc_html( $readme ) );
			$readme = explode( '== Changelog ==', $readme );
			$readme = end( $readme );
		}

		return $readme;
	}

	public function get_contributors() {
		$response = wp_remote_get( 'https://api.github.com/repos/designwall/dw-question-answer/contributors?per_page=999', array( 'sslverify' => false ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$contributors = wp_remote_retrieve_body( $response );
		$contributors = json_decode( $contributors );

		if ( !is_array( $contributors ) ) return array();

		return $contributors;
	}
}

?>