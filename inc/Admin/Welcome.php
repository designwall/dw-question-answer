<?php

class DWQA_Admin_Welcome {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
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
		<div class="about-text"><?php printf( __( 'Thank you for updating! DW Question & Answer %s makes it even easier to format your content and customize your site.' ), $dwqa->version ); ?></div>
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
				<h3><?php _e( 'Changelog', 'dwqa' ); ?></h3>
				<div class="feature-section">
					<?php echo $this->parse_readme(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function credits_layout() {
		?>
		<div class="wrap about-wrap">
			<?php $this->page_head(); ?>
			<?php $this->tabs(); ?>
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

			$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
			$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
			$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
			$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
			$readme = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme );
		}

		return $readme;
	}
}

?>