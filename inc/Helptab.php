<?php 
/**
 * Plugin Name: Help Tab Test Case
 * Plugin URI:  http://unserkaiser.com
 * Description: Add Help Tab test case
 * @since 1.3.5 
 */
class DWQA_Helptab {
	public $tabs;

	public function __construct() {
		$post_type = $this->get_current_posttype();
		if ( 'dwqa-question' == $post_type || 'dwqa-answer' == $post_type ) {
			add_action( "load-{$GLOBALS['pagenow']}", array( $this, 'add_tabs' ), 20 );
		}
	}

	public function get_current_posttype(){
		global $post, $typenow, $current_screen;
	
		//we have a post so we can just get the post type from that
		if ( $post && $post->post_type )
			return $post->post_type;

		//check the global $typenow - set in admin.php
		elseif ( $typenow )
			return $typenow;

		//check the global $current_screen object - set in sceen.php
		elseif ( $current_screen && $current_screen->post_type )
			return $current_screen->post_type;

		//lastly check the post_type querystring
		elseif ( isset( $_REQUEST['post_type'] ) ) {
			//Some plugins set post_type to an array
			if ( is_array( $_REQUEST['post_type'] ) )
				return null;
			return sanitize_key( $_REQUEST['post_type'] );
		}
	}

	private function create_tabs(){
		$this->tabs = array(
			// The assoc key represents the ID
			// It is NOT allowed to contain spaces
			'dwqa-overview' => array(
				'title'         => __( 'Overview', 'dwqa' ),
				'content'       => '<h3>'.__( 'DW Question Answer Plugin', 'dwqa' ).'</h3>'.
				'<p>'.__( 'DW Question Answer is a WordPress Plugin which helps you build a Question & Answer system on your WordPress sites. The plugin is easy to install and set up. Let start building up your community with this WordPress question & answer system.', 'dwqa' ).'</p>'.$this->help_tab_designwall()
			),
			'dwqa-guide-add-list-page' => array(
				'title'         => __( 'Setup Question List Page', 'dwqa' ),
				'content'       => $this->help_tab_guide_make_question_list_page()
			),
			'dwqa-guide-add-ask-page' => array(
				'title'         => __( 'Setup Ask Question Page', 'dwqa' ),
				'content'       => $this->help_tab_guide_make_ask_question_page()
			),
			'dwqa-guide-shortcode' => array(
				'title'         => __( 'Shortcode', 'dwqa' ),
				'content'       => $this->help_tab_guide_shortcode()
			),
			'dwqa-guide-style-integration' => array(
				'title'         => __( 'Style integration', 'dwqa' ),
				'content'       => $this->help_tab_guide_style_integration()
			)
		);
	}

	private function help_tab_designwall(){
		ob_start();
		?>
		<h3>Who is DesignWall?</h3>
		<p>We are the professional WordPress themes and plugins provider. We commit to deliver high quality WordPress products which not only focus on the design but User Experience</p>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	private function help_tab_guide_make_question_list_page(){
		ob_start();
		?>
		<h3>Setup Question List Page</h3>

		This page is to display all questions. To set up this page, please follow our instructions here:

		<span class="label label-warning">Step 1</span> Log in to <strong>Dashboard &gt;&gt; Pages &gt;&gt; Add New</strong>.

		[caption id="" align="aligncenter" width="580"]<img title="dw_question_and_answer_add_question_page" alt="add question page" src="http://designwall.s3.amazonaws.com/images/guide/dw_qa/add-new-question-page.png" width="580" height="457" /> Add Question Page[/caption]

		<span class="label label-warning">Step 2</span> Set up a menu link for this page. Go to <strong>Appearance &gt;&gt; Menus</strong> to add the page from Step 1 to Menu.

		[caption id="" align="aligncenter" width="580"]<img title="dw_question_and_answer_add_page_to_menu" alt="add pages to menu" src="http://designwall.s3.amazonaws.com/images/guide/dw_qa/add-question-page-to-menu.png" width="580" height="342" /> Add Question Page To Menu[/caption]

		<span class="label label-warning">Step 3 </span>Go to <strong>Dashboard &gt;&gt; Questions &gt;&gt; Settings &gt;&gt; General &gt;&gt; Question List Page.
		</strong>

		[caption id="" align="aligncenter" width="580"]<img title="dw_question_and_answer_question_list_page" alt="Select question list page" src="http://designwall.s3.amazonaws.com/images/guide/dw_qa/question-list-page.png" width="580" height="182" /> Select Question List Page[/caption]
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return apply_filters( 'the_content', $html );
	}


	private function help_tab_guide_make_ask_question_page(){
		ob_start();
		?>
		<h3 id="ask_question_page">Set up Ask Question Page</h3>
		<div class="alert">This section is only when you need to re-create the pages. On the latest version of DW Question &amp; Answer plugin, these pages are automatically created and assigned in the back-end, so you will NOT need to follow this section any more.</div>
		<span class="label label-warning">Step 1</span> Log in to <strong>Dashboard &gt;&gt; Pages &gt;&gt; Add New</strong>

		[caption id="" align="aligncenter" width="578"]<img title="dw_question_and_answer_add_ask_question_page" alt="Add Ask Question Page " src="http://designwall.s3.amazonaws.com/images/guide/dw_qa/add-submit-question-page.png" width="578" height="457" /> add New Page[/caption]

		<span class="label label-warning">Step 2</span> Go to <strong>Appearance &gt;&gt; Menus </strong>and add the page from Step 1 to Menu.<strong>
		</strong>

		[caption id="" align="aligncenter" width="580"]<img title="dw_question_and_answer_add_ask_question_page_to_menu" alt="add question page to menu" src="http://designwall.s3.amazonaws.com/images/guide/dw_qa/add-page-to-menu.png" width="580" height="312" /> Add Page To Menu[/caption]

		<span class="label label-warning">Step 3</span> Go to <strong>Questions &gt;&gt; Settings &gt;&gt; General &gt;&gt; Ask Question Page.
		</strong>

		[caption id="" align="aligncenter" width="580"]<img class=" " title="dw_question_and_answer_ask_question_page_settings" alt="Ask Question Page Settings" src="http://designwall.s3.amazonaws.com/images/guide/dw_qa/submit-question-page-settings.png" width="580" height="229" /> Ask Question Page Settings[/caption]
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return apply_filters( 'the_content', $html );
	}

	private function help_tab_guide_shortcode(){
		ob_start();
		?>
		<h3>Shortcodes</h3>
		In this version of the DW Question and Answer plugin, we have added the shortcode to support the some anticipated functions like: Popular Questions, Latest Answers, Question List and Ask Question Form functions. One extra for Question follow function.

		<code>[ dwqa-popular-questions ]
			 [ dwqa-latest-answers ]
			 [ dwqa-list-questions ]
			 [ dwqa-submit-question-form ]
			 [ dwqa-question-followers ]</code>
		You can place the shortcode anywhere you want to,even widgets.
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return apply_filters( 'the_content', $html );
	}

	private function help_tab_guide_style_integration() {
		ob_start();
		?>
		<h3 id="Style_integration">Style integration</h3>
		The DW Question &amp; Answer plugin can work well on any WordPress site, however, in order to get the plugin fit well in the style, we will need to work on CSS a bit. <a target="_blank" href="http://www.designwall.com/guide/dw-question-answer-plugin/#Style_integration"><?php _e( 'Read more', 'dwqa' ) ?></a>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return apply_filters( 'the_content', $html );
	}

	public function add_tabs() {
		$this->create_tabs();

		foreach ( $this->tabs as $id => $data ) {
			get_current_screen()->add_help_tab( array(
				'id'       => $id,
				'title'    => __( $data['title'], 'dwqa' ),
				// Use the content only if you want to add something
				// static on every help tab. Example: Another title inside the tab
				'content'  => $data['content'],
			) );
		}
		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p><a href="http://www.designwall.com/guide/dw-question-answer-plugin/" target="_blank">' . __( 'DW Question Answer Guide' ) . '</a></p>' .
			'<p><a href="http://www.designwall.com/question/" target="_blank">' . __( 'Community' ) . '</a></p>'.
			'<p><a href="http://www.designwall.com/wordpress/themes/" target="_blank">' . __( 'DesignWall Wordpress Themes' ) . '</a></p>'.
			'<p><a href="http://www.designwall.com/wordpress/plugins/" target="_blank">' . __( 'DesignWall Wordpress Plugins' ) . '</a></p>'
		);
	}
}


?>
