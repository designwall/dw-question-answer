<?php

class DWQA_Admin_Extensions {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_extension_menu' ) );
	}

	function register_extensions() {
		global $dwqa;
		$extension = array(
			'dwqa-markdown' => array(
				'name' => __( 'DWQA Markdown', 'dwqa' ),
				'url' => 'http://bit.ly/dwqa-markdown',
				'img_url' => $dwqa->uri . 'assets/img/dw-markdown.png'
			),

			'dwqa-leaderboard' => array(
				'name' => __( 'DWQA Leaderboard', 'dwqa' ),
				'url' => 'http://bit.ly/dwqa-leaderboard',
				'img_url' => $dwqa->uri . 'assets/img/dw-leaderboard.png'
			),

			'dwqa-captcha' => array(
				'name' => __( 'DWQA Captcha', 'dwqa' ),
				'url' => 'http://bit.ly/dwqa-captcha',
				'img_url' => $dwqa->uri . 'assets/img/dw-captcha.png',
			),

			'dwqa-embed-question' => array(
				'name' => __( 'DWQA Embed Question', 'dwqa' ),
				'url' => 'http://bit.ly/dwqa-embed-questions',
				'img_url' => $dwqa->uri . 'assets/img/dw-embedquestion.png'
			),

			'dwqa-widgets' => array(
				'name' => __( 'DWQA Widgets', 'dwqa' ),
				'url'	=> 'http://bit.ly/dwqa-widgets',
				'img_url'	=> $dwqa->uri . 'assets/img/dw-widgets.png'
			),
		);

		return $extension;
	}

	function register_extension_menu() {
		add_submenu_page( 'edit.php?post_type=dwqa-question', __( 'Extensions', 'dwqa' ), sprintf( '<span style="color: #d54e21;">%s</span>', __( 'Extensions', 'dwqa' ) ), 'manage_options', 'dwqa-extensions', array( $this, 'extension_menu_layout' ) );
	}

	function extension_menu_layout() {
		$extensions = $this->register_extensions();
		?>
		<div class="wrap">
			<h1>
				<?php echo get_admin_page_title() ?>
				<span class="title-count theme-count"><?php echo count( $extensions ); ?></span>
			</h1>
			<br>
			<div class="theme-browser">
				<div class="themes">
					<?php foreach( $extensions as $slug => $info ) : ?>
						<div class="theme">
							<?php if ( !empty( $info['img_url'] ) ) : ?>
								<div class="theme-screenshot">
									<a target="_blank" href="<?php echo esc_url( $info['url'] ) ?>"><img src="<?php echo esc_url( $info['img_url'] ) ?>"></a>
								</div>
							<?php else : ?>
								<div class="theme-screenshot blank"></div>
							<?php endif; ?>

							<div class="theme-author"></div>

							<h2 class="theme-name" id="<?php echo esc_attr( $slug ) ?>"><span><?php echo esc_attr( $info['name'] ) ?></span></h2>
							<div class="theme-actions">
								<a class="button button-primary" target="_blank" href="<?php echo esc_url( $info['url'] ) ?>"><?php _e( 'Get It Now!', 'dwqa' ); ?></a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}
}

?>