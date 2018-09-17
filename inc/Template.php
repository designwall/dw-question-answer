<?php

/**
 * Print class for question detail container
 */
function dwqa_breadcrumb() {
	$wpseo_internallinks = get_option('wpseo_internallinks');
	if ( function_exists( 'yoast_breadcrumb' ) && $wpseo_internallinks['breadcrumbs-enable'] === true) :
		yoast_breadcrumb( '<div class="breadcrumbs dwqa-breadcrumbs">', '</div>' );
	else:
		global $dwqa_general_settings;
		$title  = get_the_title( $dwqa_general_settings['pages']['archive-question'] );
		$search = isset( $_GET['qs'] ) ? esc_html( $_GET['qs'] ) : false;
		$author = isset( $_GET['user'] ) ? esc_html( $_GET['user'] ) : false;
		$output = '';
		if ( ! is_singular( 'dwqa-question' ) ) {
			$term     = get_query_var( 'dwqa-question_category' ) ? get_query_var( 'dwqa-question_category' ) : ( get_query_var( 'dwqa-question_tag' ) ? get_query_var( 'dwqa-question_tag' ) : false );
			$term     = get_term_by( 'slug', $term, get_query_var( 'taxonomy' ) );
			$tax_name = 'dwqa-question_tag' == get_query_var( 'taxonomy' ) ? __( 'Tag', 'dw-question-answer' ) : __( 'Category', 'dw-question-answer' );
		} else {
			$term = wp_get_post_terms( get_the_ID(), 'dwqa-question_category' );
			if ( $term ) {
				$term     = $term[0];
				$tax_name = __( 'Category', 'dw-question-answer' );
			}
		}
		if ( is_singular( 'dwqa-question' ) || $search || $author || $term ) {
			$output .= '<div class="dwqa-breadcrumbs">';
		}
		if ( $term || is_singular( 'dwqa-question' ) || $search || $author ) {
			$output .= '<a href="' . get_permalink( $dwqa_general_settings['pages']['archive-question'] ) . '">' . $title . '</a>';
		}
		if ( $term ) {
			$output .= '<span class="dwqa-sep"> &rsaquo; </span>';
			if ( is_singular( 'dwqa-question' ) ) {
				$output .= '<a href="' . esc_url( get_term_link( $term, get_query_var( 'taxonomy' ) ) ) . '">' . $tax_name . ': ' . $term->name . '</a>';
			} else {
				$output .= '<span class="dwqa-current">' . $tax_name . ': ' . $term->name . '</span>';
			}
		}
		if ( $search ) {
			$output .= '<span class="dwqa-sep"> &rsaquo; </span>';
			$output .= sprintf( '<span class="dwqa-current">%s "%s"</span>', __( 'Showing search results for', 'dw-question-answer' ), htmlspecialchars( $search ) );
		}
		if ( $author ) {
			$output .= '<span class="dwqa-sep"> &rsaquo; </span>';
			$output .= sprintf( '<span class="dwqa-current">%s "%s"</span>', __( 'Author', 'dw-question-answer' ), htmlspecialchars( $author ) );
		}
		if ( is_singular( 'dwqa-question' ) ) {
			$output .= '<span class="dwqa-sep"> &rsaquo; </span>';
			if ( ! dwqa_is_edit() ) {
				$output .= '<span class="dwqa-current">' . get_the_title() . '</span>';
			} else {
				$output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
				$output .= '<span class="dwqa-sep"> &rsaquo; </span>';
				$output .= '<span class="dwqa-current">' . __( 'Edit', 'dw-question-answer' ) . '</span>';
			}
		}
		if ( is_singular( 'dwqa-question' ) || $search || $author || $term ) {
			$output .= '</div>';
		}
		echo apply_filters( 'dwqa_breadcrumb', $output );
	endif;
	
}
add_action( 'dwqa_before_questions_archive', 'dwqa_breadcrumb' );
add_action( 'dwqa_before_single_question', 'dwqa_breadcrumb' );

function dwqa_archive_question_filter_layout() {
	dwqa_load_template( 'archive', 'question-filter' );
}
add_action( 'dwqa_before_questions_archive', 'dwqa_archive_question_filter_layout', 12 );

function dwqa_search_form() {
	?>
	<form id="dwqa-search" class="dwqa-search">
		<input data-nonce="<?php echo wp_create_nonce( '_dwqa_filter_nonce' ) ?>" type="text" placeholder="<?php _e( 'What do you want to know?', 'dw-question-answer' ); ?>" name="qs" value="<?php echo isset( $_GET['qs'] ) ? esc_html( $_GET['qs'] ) : '' ?>">
	</form>
	<?php
}
add_action( 'dwqa_before_questions_archive', 'dwqa_search_form', 11 );

function dwqa_class_for_question_details_container(){
	$class = array();
	$class[] = 'question-details';
	$class = apply_filters( 'dwqa-class-questions-details-container', $class );
	echo implode( ' ', $class );
}

add_action( 'dwqa_after_answers_list', 'dwqa_answer_paginate_link' );
function dwqa_answer_paginate_link() {
	global $wp_query;
	$question_url = get_permalink();
	$page = isset( $_GET['ans-page'] ) ? intval( $_GET['ans-page'] ) : 1;

	$args = array(
		'base' => add_query_arg( 'ans-page', '%#%', $question_url ),
		'format' => '',
		'current' => $page,
		'total' => $wp_query->dwqa_answers->max_num_pages
	);

	$paginate = paginate_links( $args );
	$paginate = str_replace( 'page-number', 'dwqa-page-number', $paginate );
	$paginate = str_replace( 'current', 'dwqa-current', $paginate );
	$paginate = str_replace( 'next', 'dwqa-next', $paginate );
	$paginate = str_replace( 'prev ', 'dwqa-prev ', $paginate );
	$paginate = str_replace( 'dots', 'dwqa-dots', $paginate );

	if ( $wp_query->dwqa_answers->max_num_pages > 1 ) {
		echo '<div class="dwqa-pagination">';
		echo $paginate;
		echo '</div>';
	}
}

function dwqa_question_paginate_link() {
	global $wp_query, $dwqa_general_settings, $dwqa_atts;

	$archive_question_url = get_permalink( $dwqa_general_settings['pages']['archive-question'] );
	$page_text = dwqa_is_front_page() ? 'page' : 'paged';
	$page = get_query_var( $page_text ) ? get_query_var( $page_text ) : 1;

	$tag = get_query_var( 'dwqa-question_tag' ) ? get_query_var( 'dwqa-question_tag' ) : false;
	$cat = get_query_var( 'dwqa-question_category' ) ? get_query_var( 'dwqa-question_category' ) : false;

	$url = $cat 
			? get_term_link( $cat, get_query_var( 'taxonomy' ) ) 
			: ( $tag ? get_term_link( $tag, get_query_var( 'taxonomy' ) ) : $archive_question_url );

	if(isset($dwqa_atts['category']) && isset($dwqa_atts['page_id']) && $dwqa_atts['page_id']){
		$url = get_permalink($dwqa_atts['page_id']);
	}

	$args = array(
		'base' => add_query_arg( $page_text, '%#%', $url ),
		'format' => '',
		'current' => $page,
		'total' => $wp_query->dwqa_questions->max_num_pages
	);

	$paginate = paginate_links( $args );
	$paginate = str_replace( 'page-number', 'dwqa-page-number', $paginate );
	$paginate = str_replace( 'current', 'dwqa-current', $paginate );
	$paginate = str_replace( 'next', 'dwqa-next', $paginate );
	$paginate = str_replace( 'prev ', 'dwqa-prev ', $paginate );
	$paginate = str_replace( 'dots', 'dwqa-dots', $paginate );

	if ( $wp_query->dwqa_questions->max_num_pages > 1 ) {
		echo '<div class="dwqa-pagination">';
		echo $paginate;
		echo '</div>';
	}
}

function dwqa_question_button_action() {
	$html = '';
	if ( is_user_logged_in() ) {
		$followed = dwqa_is_followed() ? 'followed' : 'follow';
		$text = __( 'Subscribe', 'dw-question-answer' );
		$html .= '<label for="dwqa-favorites">';
		$html .= '<input type="checkbox" id="dwqa-favorites" data-post="'. get_the_ID() .'" data-nonce="'. wp_create_nonce( '_dwqa_follow_question' ) .'" value="'. $followed .'" '. checked( $followed, 'followed', false ) .'/>';
		$html .= '<span>' . $text . '</span>';
		$html .= '</label>';
		if ( dwqa_current_user_can( 'edit_question' ) ) {
			$html .= '<a class="dwqa_edit_question" href="'. add_query_arg( array( 'edit' => get_the_ID() ), get_permalink() ) .'">' . __( 'Edit', 'dw-question-answer' ) . '</a> ';
		}

		if ( dwqa_current_user_can( 'delete_question' ) ) {
			$action_url = add_query_arg( array( 'action' => 'dwqa_delete_question', 'question_id' => get_the_ID() ), admin_url( 'admin-ajax.php' ) );
			$html .= '<a class="dwqa_delete_question" href="'. wp_nonce_url( $action_url, '_dwqa_action_remove_question_nonce' ) .'">' . __( 'Delete', 'dw-question-answer' ) . '</a> ';
		}
	}

	echo apply_filters( 'dwqa_question_button_action', $html );
}

function dwqa_answer_button_action() {
	$html = '';
	if ( is_user_logged_in() ) {
		if ( dwqa_current_user_can( 'edit_answer' ) ) {
			$parent_id = dwqa_get_question_from_answer_id();
			$html .= '<a class="dwqa_edit_question" href="'. add_query_arg( array( 'edit' => get_the_ID() ), get_permalink( $parent_id ) ) .'">' . __( 'Edit', 'dw-question-answer' ) . '</a> ';
		}

		if ( dwqa_current_user_can( 'delete_answer' ) ) {
			$action_url = add_query_arg( array( 'action' => 'dwqa_delete_answer', 'answer_id' => get_the_ID() ), admin_url( 'admin-ajax.php' ) );
			$html .= '<a class="dwqa_delete_answer" href="'. wp_nonce_url( $action_url, '_dwqa_action_remove_answer_nonce' ) .'">' . __( 'Delete', 'dw-question-answer' ) . '</a> ';
		}
	}

	echo apply_filters( 'dwqa_answer_button_action', $html );
}


function dwqa_question_add_class( $classes, $class, $post_id ){
	if ( get_post_type( $post_id ) == 'dwqa-question' ) {

		$have_new_reply = dwqa_have_new_reply();
		if ( $have_new_reply == 'staff-answered' ) {
			$classes[] = 'staff-answered';
		}
	}
	return $classes;
}
add_action( 'post_class', 'dwqa_question_add_class', 10, 3 );

/**
 * callback for comment of question
 */
function dwqa_answer_comment_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	global $post;

	if ( get_user_by( 'id', $comment->user_id ) ) {
		dwqa_load_template( 'content', 'comment' );
	}
}

function dwqa_question_comment_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	global $post;
	dwqa_load_template( 'content', 'comment' );
}

function dwqa_body_class( $classes ) {
	global $post, $dwqa_options;
	if ( ( $dwqa_options['pages']['archive-question'] && is_page( $dwqa_options['pages']['archive-question'] )  )
		|| ( is_archive() &&  ( 'dwqa-question' == get_post_type()
				|| 'dwqa-question' == get_query_var( 'post_type' )
				|| 'dwqa-question_category' == get_query_var( 'taxonomy' )
				|| 'dwqa-question_tag' == get_query_var( 'taxonomy' ) ) )
	){
		$classes[] = 'list-dwqa-question';
	}

	if ( $dwqa_options['pages']['submit-question'] && is_page( $dwqa_options['pages']['submit-question'] ) ){
		$classes[] = 'submit-dwqa-question';
	}
	return $classes;
}
add_filter( 'body_class', 'dwqa_body_class' );


/**
 * Add Icon for DW Question Answer Menu In Dashboard
 */
function dwqa_add_guide_menu_icons_styles(){
	echo '<style type="text/css">#adminmenu .menu-icon-dwqa-question div.wp-menu-image:before {content: "\f223";}</style>';
}
add_action( 'admin_head', 'dwqa_add_guide_menu_icons_styles' );

function dwqa_load_template( $name, $extend = false, $include = true ){
	global $dwqa;
	$dwqa->template->load_template( $name, $extend, $include );
}

function dwqa_post_class( $post_id = false ) {
	$classes = array();

	if ( !$post_id ) {
		$post_id = get_the_ID();
	}

	if ( 'dwqa-question' == get_post_type( $post_id ) ) {
		$classes[] = 'dwqa-question-item';

		if ( !is_singular( 'dwqa-question' ) && dwqa_is_sticky( $post_id ) ) {
			$classes[] = 'dwqa-sticky';
		}
	}

	if ( 'dwqa-answer' == get_post_type( $post_id ) ) {
		$classes[] = 'dwqa-answer-item';

		if ( dwqa_is_the_best_answer( $post_id ) ) {
			$classes[] = 'dwqa-best-answer';
		}

		if ( 'private' == get_post_status( $post_id ) ) {
			$classes[] = 'dwqa-status-private';
		}
	}

	return implode( ' ', apply_filters( 'dwqa_post_class', $classes ) );
}

/**
 * Enqueue all scripts for plugins on front-end
 * @return void
 */
function dwqa_enqueue_scripts(){
    global $dwqa, $dwqa_options, $script_version, $dwqa_sript_vars, $dwqa_general_settings;
    $template_name = $dwqa->template->get_template();

	$question_category_rewrite = $dwqa_general_settings['question-category-rewrite'];
    $question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
	$question_tag_rewrite = $dwqa_general_settings['question-tag-rewrite'];
    $question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';

    $assets_folder = DWQA_URI . 'templates/assets/';
    wp_enqueue_script( 'jquery' );
    if( is_singular( 'dwqa-question' ) ) {
        wp_enqueue_script( 'jquery-effects-core' );
        wp_enqueue_script( 'jquery-effects-highlight' );
    }
    $script_version = $dwqa->get_last_update();

    // Enqueue style
    wp_enqueue_style( 'dwqa-style', $assets_folder . 'css/style.css', array(), $script_version );
    wp_enqueue_style( 'dwqa-rtl', $assets_folder . 'css/rtl.css', array(), $script_version );
    // Enqueue for single question page
    if( is_single() && 'dwqa-question' == get_post_type() ) {
        // js
        wp_enqueue_script( 'dwqa-single-question', $assets_folder . 'js/dwqa-single-question.js', array('jquery'), $script_version, true );
        $single_script_vars = $dwqa_sript_vars;
        $single_script_vars['question_id'] = get_the_ID();
        wp_localize_script( 'dwqa-single-question', 'dwqa', $single_script_vars );
    }

    $question_category = get_query_var( 'dwqa-question_category' );
    if ( $question_category ) {
		$question_category_rewrite = $dwqa_options['question-category-rewrite'] ? $dwqa_options['question-category-rewrite'] : 'question-category';
    	$dwqa_sript_vars['taxonomy'][$question_category_rewrite] = $question_category;
    }
    $question_tag = get_query_var( 'dwqa-question_tag' );
    if ( $question_tag ) {
		$question_tag_rewrite = $dwqa_options['question-tag-rewrite'] ? $dwqa_options['question-tag-rewrite'] : 'question-category';
    	$dwqa_sript_vars['taxonomy'][$question_tag_rewrite] = $question_tag;
    }
    if( (is_archive() && 'dwqa-question' == get_post_type()) || ( isset( $dwqa_options['pages']['archive-question'] ) && is_page( $dwqa_options['pages']['archive-question'] ) ) ) {
        wp_enqueue_script( 'dwqa-questions-list', $assets_folder . 'js/dwqa-questions-list.js', array( 'jquery' ), $script_version, true );
        wp_localize_script( 'dwqa-questions-list', 'dwqa', $dwqa_sript_vars );
    }

    if( isset($dwqa_options['pages']['submit-question'])
        && is_page( $dwqa_options['pages']['submit-question'] ) ) {
    	wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'dwqa-submit-question', $assets_folder . 'js/dwqa-submit-question.js', array( 'jquery', 'jquery-ui-autocomplete' ), $script_version, true );
        wp_localize_script( 'dwqa-submit-question', 'dwqa', $dwqa_sript_vars );
    }
}
add_action( 'wp_enqueue_scripts', 'dwqa_enqueue_scripts' );

add_action( 'wp_footer', 'dwqa_wp_footer' );
function dwqa_wp_footer() {
	global $dwqa_general_settings;

	if ( isset( $dwqa_general_settings['show-status-icon'] ) && $dwqa_general_settings['show-status-icon'] && dwqa_is_enable_status() ) {
		?>
		<style type="text/css">
			@import url('https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');

			.dwqa-questions-list .dwqa-question-item {
				padding-left: 70px;
			}

			.dwqa-questions-list .dwqa-question-item .avatar {
				position: static;
				width: 12px;
				height: 12px;
				margin-right: 5px;
				margin-top: 0;
				display: inline-block;
			}

			.dwqa-question-item .dwqa-status {
				position: absolute;
				left: 15px;
				top: 50%;
				width: 36px;
				height: 36px;
				margin-top: -18px;
				border-radius: 36px;
				text-indent: -9999px;
				padding: 0;
				background: none;
				box-shadow: 0 0 0 1px #e67e22 inset;
			}

			.dwqa-question-item .dwqa-status:after {
				content: "\f128";
				display: block;
				font: normal normal normal 14px/1 FontAwesome;
				font-size: inherit;
				text-rendering: auto;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;

				color: #e67e22;
				text-indent: 0;
				font-size: 18px;
				width: 36px;
				height: 36px;
				line-height: 36px;
				text-align: center;
				top: 0;
				position: absolute;
			}

			.dwqa-question-item .dwqa-status-closed {
				box-shadow: 0 0 0 1px #666 inset;
			}

			.dwqa-question-item .dwqa-status-closed:after {
				color: #666;
				content: "\f023";
			}

			.dwqa-question-item .dwqa-status-resolved {
				box-shadow: 0 0 0 1px #578824 inset;
			}

			.dwqa-question-item .dwqa-status-resolved:after {
				color: #578824;
				content: "\f00c";
			}

			.dwqa-question-item .dwqa-status-answered {
				box-shadow: 0 0 0 1px #1ba1e2 inset;
			}

			.dwqa-question-item .dwqa-status-answered:after {
				color: #1ba1e2;
				content: "\f112";
				font-size: 14px;
			}
		</style>
		<?php
	}
}

function dwqa_comment_form( $args = array(), $post_id = null ) {
	if ( null === $post_id )
		$post_id = get_the_ID();
	else
		$id = $post_id;
	$commenter = wp_get_current_commenter();
	$user = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : '';
	$args = wp_parse_args( $args );
	if ( ! isset( $args['format'] ) )
		$args['format'] = current_theme_supports( 'html5', 'comment-form' ) ? 'html5' : 'xhtml';
	$req      = get_option( 'require_name_email' );
	$aria_req = ( $req ? " aria-required='true'" : '' );
	$html5    = 'html5' === $args['format'];
	$fields   = array(
		'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'dw-question-answer' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
					'<input id="email-'.$post_id.'" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
		'author'  => '<p class="comment-form-name"><label for="name">' . __( 'Name', 'dw-question-answer' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label>' . '<input id="name-' .$post_id.'" name="name" type="text" value="" size="30"/></p>'
	);
	$required_text = sprintf( ' ' . __( 'Required fields are marked %s' ), '<span class="required">*</span>' );
	/**
	 * Filter the default comment form fields.
	 *
	 * @since 3.0.0
	 *
	 * @param array $fields The default comment fields.
	 */
	$fields = apply_filters( 'comment_form_default_fields', $fields );
	$defaults = array(
		'fields'               => $fields,
		'comment_field'        => '',
		'must_log_in'          => '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.','dw-question-answer' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		'logged_in_as'         => '<p class="comment-form-comment"><textarea id="comment" name="comment" placeholder="Comment" rows="2" aria-required="true"></textarea></p>',
		'comment_notes_before' => '<p class="comment-form-comment"><textarea id="comment" name="comment" placeholder="Comment" rows="2" aria-required="true"></textarea></p>',
		'comment_notes_after'  => '<p class="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s','dw-question-answer' ), ' <code>' . allowed_tags() . '</code>' ) . '</p>',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'title_reply'          => __( 'Leave a Reply','dw-question-answer' ),
		'title_reply_to'       => __( 'Leave a Reply to %s','dw-question-answer' ),
		'cancel_reply_link'    => __( 'Cancel reply', 'dw-question-answer' ),
		'label_submit'         => __( 'Post Comment', 'dw-question-answer' ),
		'format'               => 'xhtml',
	);
	/**
	 * Filter the comment form default arguments.
	 *
	 * Use 'comment_form_default_fields' to filter the comment fields.
	 *
	 * @since 3.0.0
	 *
	 * @param array $defaults The default comment form arguments.
	 */
	$args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );
	if ( comments_open( $post_id ) ) :
		/**
		 * Fires before the comment form.
		 *
		 * @since 3.0.0
		 */
		do_action( 'comment_form_before' );
		?>
		<div id="dwqa-respond" class="dwqa-comment-form">
		<?php if ( !dwqa_current_user_can( 'post_comment' ) ) : ?>
			<?php echo $args['must_log_in']; ?>
			<?php
			/**
			 * Fires after the HTML-formatted 'must log in after' message in the comment form.
			 *
			 * @since 3.0.0
			 */
			do_action( 'comment_form_must_log_in_after' );
			?>
		<?php else : ?>
			<form method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>" class="comment-form"<?php echo $html5 ? ' novalidate' : ''; ?>>
			<?php
			/**
			 * Fires at the top of the comment form, inside the <form> tag.
			 *
			 * @since 3.0.0
			 */
			do_action( 'comment_form_top' );
			?>
			<?php if ( is_user_logged_in() ) : ?>
				<?php
				/**
				 * Filter the 'logged in' message for the comment form for display.
				 *
				 * @since 3.0.0
				 *
				 * @param string $args['logged_in_as'] The logged-in-as HTML-formatted message.
				 * @param array  $commenter            An array containing the comment author's username, email, and URL.
				 * @param string $user_identity        If the commenter is a registered user, the display name, blank otherwise.
				 */
				echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity );
				?>
				<?php
				/**
				 * Fires after the is_user_logged_in() check in the comment form.
				 *
				 * @since 3.0.0
				 *
				 * @param array  $commenter     An array containing the comment author's username, email, and URL.
				 * @param string $user_identity If the commenter is a registered user, the display name, blank otherwise.
				 */
				do_action( 'comment_form_logged_in_after', $commenter, $user_identity );
				?>
			<?php else : ?>
				<?php echo $args['comment_notes_before']; ?>
				<?php
				/**
				 * Fires before the comment fields in the comment form.
				 *
				 * @since 3.0.0
				 */
				do_action( 'comment_form_before_fields' );
				echo '<div class="dwqa-anonymous-fields">';
				foreach ( (array ) $args['fields'] as $name => $field ) {
					/**
					 * Filter a comment form field for display.
					 *
					 * The dynamic portion of the filter hook, $name, refers to the name
					 * of the comment form field. Such as 'author', 'email', or 'url'.
					 *
					 * @since 3.0.0
					 *
					 * @param string $field The HTML-formatted output of the comment form field.
					 */
					echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";
				}
				echo '</div>';
				/**
				 * Fires after the comment fields in the comment form.
				 *
				 * @since 3.0.0
				 */
				do_action( 'comment_form_after_fields' );
				?>
			<?php endif; ?>
			<?php
			/**
			 * Filter the content of the comment textarea field for display.
			 *
			 * @since 3.0.0
			 *
			 * @param string $args['comment_field'] The content of the comment textarea field.
			 */
			echo apply_filters( 'comment_form_field_comment', $args['comment_field'] );
			?>
			<input name="comment-submit" type="submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" value="<?php echo esc_attr( $args['label_submit'] ); ?>" class="dwqa-btn dwqa-btn-primary" />
			<?php comment_id_fields( $post_id ); ?>
			<?php
			/**
			 * Fires at the bottom of the comment form, inside the closing </form> tag.
			 *
			 * @since 1.5.0
			 *
			 * @param int $post_id The post ID.
			 */
			do_action( 'comment_form', $post_id );
			?>
			</form>
		<?php endif; ?>
		</div><!-- #respond -->
		<?php
		/**
		 * Fires after the comment form.
		 *
		 * @since 3.0.0
		 */
		do_action( 'comment_form_after' );
	else :
		/**
		 * Fires after the comment form if comments are closed.
		 *
		 * @since 3.0.0
		 */
		do_action( 'comment_form_comments_closed' );
	endif;
}

function dwqa_display_sticky_questions(){
	$sticky_questions = get_option( 'dwqa_sticky_questions', array() );
	if ( ! empty( $sticky_questions ) ) {
		$query = array(
			'post_type' => 'dwqa-question',
			'post__in' => $sticky_questions,
			'posts_per_page' => 40,
		);
		$sticky_questions = new WP_Query( $query );
		?>
		<div class="sticky-questions">
			<?php while ( $sticky_questions->have_posts() ) : $sticky_questions->the_post(); ?>
				<?php dwqa_load_template( 'content', 'question' ); ?>
			<?php endwhile; ?>
		</div>
		<?php
		wp_reset_postdata();
	}
}
add_action( 'dwqa-before-question-list', 'dwqa_display_sticky_questions' );

function dwqa_is_sticky( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$sticky_questions = get_option( 'dwqa_sticky_questions', array() );
	if ( in_array( $question_id, $sticky_questions ) ) {
		return true;
	}
	return false;
}


function dwqa_question_states( $states, $post ){
	if ( dwqa_is_sticky( $post->ID ) && 'dwqa-question' == get_post_type( $post->ID ) ) {
		$states[] = __( 'Sticky Question','dw-question-answer' );
	}
	return $states;
}
add_filter( 'display_post_states', 'dwqa_question_states', 10, 2 );


function dwqa_get_ask_question_link( $echo = true, $label = false, $class = false ){
	global $dwqa_options;
	$submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
	if ( $dwqa_options['pages']['submit-question'] && $submit_question_link ) {


		if ( dwqa_current_user_can( 'post_question' ) ) {
			$label = $label ? $label : __( 'Ask a question', 'dw-question-answer' );
		} elseif ( ! is_user_logged_in() ) {
			$label = $label ? $label : __( 'Login to ask a question', 'dw-question-answer' );
			$submit_question_link = wp_login_url( $submit_question_link );
		} else {
			return false;
		}
		//Add filter to change ask question link text
		$label = apply_filters( 'dwqa_ask_question_link_label', $label );

		$class = $class ? $class  : 'dwqa-btn-success';
		$button = '<a href="'.$submit_question_link.'" class="dwqa-btn '.$class.'">'.$label.'</a>';
		$button = apply_filters( 'dwqa_ask_question_link', $button, $submit_question_link );
		if ( ! $echo ) {
			return $button;
		}
		echo $button;
	}
}

function dwqa_get_template( $template = false ) {
	$templates = apply_filters( 'dwqa_get_template', array(
		'single-dwqa-question.php',
		'page.php',
		'single.php',
		'index.php',
	) );

	$temp_dir = array(
		1 => trailingslashit( get_stylesheet_directory() ),
		10 => trailingslashit( get_template_directory() )
	);

	if ( isset( $template ) ) {
		foreach( $temp_dir as $link ) {
			if ( file_exists( $link . $template ) ) {
				return $link . $template;
			}
		}
	}

	$old_template = $template;
	foreach ( $templates as $template ) {
		if ( $template == $old_template ) {
			continue;
		}
		foreach( $temp_dir as $link ) {
			if ( file_exists( $link . $template ) ) {
				return $link . $template;
			}
		}
	}
	return false;
}

function dwqa_has_sidebar_template() {
	global $dwqa_options, $dwqa_template;
	$template = get_stylesheet_directory() . '/dwqa-templates/';
	if ( is_single() && file_exists( $template . '/sidebar-single.php' ) ) {
		include $template . '/sidebar-single.php';
		return;
	} elseif ( is_single() ) {
		if ( file_exists( DWQA_DIR . 'inc/templates/'.$dwqa_template.'/sidebar-single.php' ) ) {
			include DWQA_DIR . 'inc/templates/'.$dwqa_template.'/sidebar-single.php';
		} else {
			get_sidebar();
		}
		return;
	}

	return;
}

add_action( 'dwqa_after_single_question_content', 'dwqa_load_answers' );
function dwqa_load_answers() {
	global $dwqa;
	$dwqa->template->load_template( 'answers' );
}

class DWQA_Template {
	private $active = 'default';
	private $page_template = 'page.php';
	public $filters;

	public function __construct() {
		$this->filters = new stdClass();
		add_filter( 'template_include', array( $this, 'question_content' ) );
		//add_filter( 'term_link', array( $this, 'force_term_link_to_setting_page' ), 10, 3 );
		add_filter( 'comments_open', array( $this, 'close_default_comment' ), 10, 2 );

		//Template Include Hook
		add_filter( 'single_template', array( $this, 'redirect_answer_to_question' ), 20 );
		add_filter( 'comments_template', array( $this, 'generate_template_for_comment_form' ), 20 );

		//Wrapper
		add_action( 'dwqa_before_page', array( $this, 'start_wrapper_content' ) );
		add_action( 'dwqa_after_page', array( $this, 'end_wrapper_content' ) );

		add_filter( 'option_thread_comments', array( $this, 'disable_thread_comment' ) );
	}

	public function start_wrapper_content() {
		$this->load_template( 'content', 'start-wrapper' );
		echo '<div class="dwqa-container" >';
	}

	public function end_wrapper_content() {
		echo '</div>';
		$this->load_template( 'content', 'end-wrapper' );
		wp_reset_query();
	}


	public function redirect_answer_to_question( $template ) {
		global $post, $dwqa_options;
		if ( is_singular( 'dwqa-answer' ) ) {
			$question_id = dwqa_get_post_parent_id( $post->ID );
			if ( $question_id ) {
				wp_safe_redirect( get_permalink( $question_id ) );
				exit( 0 );
			}
		}
		return $template;
	}

	public function generate_template_for_comment_form( $comment_template ) {
		if (  is_single() && ('dwqa-question' == get_post_type() || 'dwqa-answer' == get_post_type() ) ) {
			return $this->load_template( 'comments', false, false );
		}
		return $comment_template;
	}

	public function page_template_body_class( $classes ) {
		$classes[] = 'page-template';

		$template_slug  = $this->page_template;
		$template_parts = explode( '/', $template_slug );

		foreach ( $template_parts as $part ) {
			$classes[] = 'page-template-' . sanitize_html_class( str_replace( array( '.', '/' ), '-', basename( $part, '.php' ) ) );
			$classes[] = sanitize_html_class( str_replace( array( '.', '/' ), '-', basename( $part, '.php' ) ) );
		}
		$classes[] = 'page-template-' . sanitize_html_class( str_replace( '.', '-', $template_slug ) );

		return $classes;
	}

	public function question_content( $template ) {
		global $wp_query;
		$dwqa_options = get_option( 'dwqa_options' );
		$template_folder = trailingslashit( get_template_directory() );
		if ( isset( $dwqa_options['pages']['archive-question'] ) ) {
			$page_template = get_post_meta( $dwqa_options['pages']['archive-question'], '_wp_page_template', true );
		}

		$page_template = isset( $page_template ) && !empty( $page_template ) ? $page_template : 'page.php';
		$this->page_template = $page_template;

		if ( is_singular( 'dwqa-question' ) ) {
			ob_start();

			remove_filter( 'comments_open', array( $this, 'close_default_comment' ) );

			echo '<div class="dwqa-container" >';
			$this->load_template( 'single', 'question' );
			echo '</div>';

			$content = ob_get_contents();

			add_filter( 'comments_open', array( $this, 'close_default_comment' ), 10, 2 );

			ob_end_clean();

			// Reset post
			global $post, $current_user;

			$this->reset_content( array(
				'ID'             => $post->ID,
				'post_title'     => $post->post_title,
				'post_author'    => 0,
				'post_date'      => $post->post_date,
				'post_content'   => $content,
				'post_type'      => 'dwqa-question',
				'post_status'    => $post->post_status,
				'is_single'      => true,
			) );

			$single_template = isset( $dwqa_options['single-template'] ) ? $dwqa_options['single-template'] : false;

			$this->remove_all_filters( 'the_content' );
			add_filter( 'body_class', array( $this, 'page_template_body_class' ) );
			return dwqa_get_template( $page_template );
		}
		if ( is_tax( 'dwqa-question_category' ) || is_tax( 'dwqa-question_tag' ) || is_post_type_archive( 'dwqa-question' ) || is_post_type_archive( 'dwqa-answer' ) || isset( $wp_query->query_vars['dwqa-question_tag'] ) || isset( $wp_query->query_vars['dwqa-question_category'] ) ) {

			$post_id = isset( $dwqa_options['pages']['archive-question'] ) ? $dwqa_options['pages']['archive-question'] : 0;
			if ( $post_id ) {
				$page = get_post( $post_id );
				if ( is_tax( 'dwqa-question_category' ) || is_tax( 'dwqa-question_tag' ) ) {
					$page->is_tax = true;
				}
				$this->reset_content( $page );
				add_filter( 'body_class', array( $this, 'page_template_body_class' ) );
				return dwqa_get_template( $page_template );
			}
		}

		if ( is_page( $dwqa_options['pages']['archive-question'] ) ) {
			$wp_query->is_archive = true;
		}

		return $template;
	}

	public function reset_content( $args ) {
		global $wp_query, $post;
		if ( isset( $wp_query->post ) ) {
			$dummy = wp_parse_args( $args, array(
				'ID'                    => $wp_query->post->ID,
				'post_status'           => $wp_query->post->post_status,
				'post_author'           => $wp_query->post->post_author,
				'post_parent'           => $wp_query->post->post_parent,
				'post_type'             => $wp_query->post->post_type,
				'post_date'             => $wp_query->post->post_date,
				'post_date_gmt'         => $wp_query->post->post_date_gmt,
				'post_modified'         => $wp_query->post->post_modified,
				'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
				'post_content'          => $wp_query->post->post_content,
				'post_title'            => $wp_query->post->post_title,
				'post_excerpt'          => $wp_query->post->post_excerpt,
				'post_content_filtered' => $wp_query->post->post_content_filtered,
				'post_mime_type'        => $wp_query->post->post_mime_type,
				'post_password'         => $wp_query->post->post_password,
				'post_name'             => $wp_query->post->post_name,
				'guid'                  => $wp_query->post->guid,
				'menu_order'            => $wp_query->post->menu_order,
				'pinged'                => $wp_query->post->pinged,
				'to_ping'               => $wp_query->post->to_ping,
				'ping_status'           => $wp_query->post->ping_status,
				'comment_status'        => $wp_query->post->comment_status,
				'comment_count'         => $wp_query->post->comment_count,
				'filter'                => $wp_query->post->filter,

				'is_404'                => false,
				'is_page'               => false,
				'is_single'             => false,
				'is_archive'            => false,
				'is_tax'                => false,
				'current_comment'		=> 0,
			) );
		} else {
			$dummy = wp_parse_args( $args, array(
				'ID'                    => -1,
				'post_status'           => 'private',
				'post_author'           => 0,
				'post_parent'           => 0,
				'post_type'             => 'page',
				'post_date'             => 0,
				'post_date_gmt'         => 0,
				'post_modified'         => 0,
				'post_modified_gmt'     => 0,
				'post_content'          => '',
				'post_title'            => '',
				'post_excerpt'          => '',
				'post_content_filtered' => '',
				'post_mime_type'        => '',
				'post_password'         => '',
				'post_name'             => '',
				'guid'                  => '',
				'menu_order'            => 0,
				'pinged'                => '',
				'to_ping'               => '',
				'ping_status'           => '',
				'comment_status'        => 'closed',
				'comment_count'         => 0,
				'filter'                => 'raw',

				'is_404'                => false,
				'is_page'               => false,
				'is_single'             => false,
				'is_archive'            => false,
				'is_tax'                => false,
				'current_comment'		=> 0,
			) );
		}
		// Bail if dummy post is empty
		if ( empty( $dummy ) ) {
			return;
		}
		// Set the $post global
		$post = new WP_Post( (object ) $dummy );
		setup_postdata( $post );
		// Copy the new post global into the main $wp_query
		$wp_query->post       = $post;
		$wp_query->posts      = array( $post );

		// Prevent comments form from appearing
		$wp_query->post_count 		= 1;
		$wp_query->is_404     		= $dummy['is_404'];
		$wp_query->is_page    		= $dummy['is_page'];
		$wp_query->is_single  		= $dummy['is_single'];
		$wp_query->is_archive 		= $dummy['is_archive'];
		$wp_query->is_tax     		= $dummy['is_tax'];
		$wp_query->current_comment 	= $dummy['current_comment'];

	}

	function disable_thread_comment( $value ) {
		if ( is_singular( 'dwqa-question' ) ) {
			return false;
		}

		return $value;
	}

	public function close_default_comment( $open, $post_id ) {
		global $dwqa_options;

		if ( get_post_type( $post_id ) == 'dwqa-question' || get_post_type( $post_id ) == 'dwqa-answer' || ( $dwqa_options['pages']['archive-question'] && $dwqa_options['pages']['archive-question'] == $post_id) || ( $dwqa_options['pages']['submit-question'] && $dwqa_options['pages']['submit-question'] == $post_id) ) {
			return false;
		}
		return $open;
	}

	public function remove_all_filters( $tag, $priority = false ) {
		global $wp_filter, $merged_filters;

		// Filters exist
		if ( isset( $wp_filter[$tag] ) ) {

			// Filters exist in this priority
			if ( ! empty( $priority ) && isset( $wp_filter[$tag][$priority] ) ) {

				// Store filters in a backup
				$this->filters->wp_filter[$tag][$priority] = $wp_filter[$tag][$priority];

				// Unset the filters
				unset( $wp_filter[$tag][$priority] );

				// Priority is empty
			} else {

				// Store filters in a backup
				$this->filters->wp_filter[$tag] = $wp_filter[$tag];

				// Unset the filters
				unset( $wp_filter[$tag] );
			}
		}

		// Check merged filters
		if ( isset( $merged_filters[$tag] ) ) {

			// Store filters in a backup
			$this->filters->merged_filters[$tag] = $merged_filters[$tag];

			// Unset the filters
			unset( $merged_filters[$tag] );
		}

		return true;
	}

	public function restore_all_filters( $tag, $priority = false ) {
		global $wp_filter, $merged_filters;

		// Filters exist
		if ( isset( $this->filters->wp_filter[$tag] ) ) {

			// Filters exist in this priority
			if ( ! empty( $priority ) && isset( $this->filters->wp_filter[$tag][$priority] ) ) {

				// Store filters in a backup
				$wp_filter[$tag][$priority] = $this->filters->wp_filter[$tag][$priority];

				// Unset the filters
				unset( $this->filters->wp_filter[$tag][$priority] );
				// Priority is empty
			} else {

				// Store filters in a backup
				$wp_filter[$tag] = $this->filters->wp_filter[$tag];

				// Unset the filters
				unset( $this->filters->wp_filter[$tag] );
			}
		}

		// Check merged filters
		if ( isset( $this->filters->merged_filters[$tag] ) ) {

			// Store filters in a backup
			$merged_filters[$tag] = $this->filters->merged_filters[$tag];

			// Unset the filters
			unset( $this->filters->merged_filters[$tag] );
		}

		return true;
	}

	public function get_template() {
		return $this->active;
	}

	public function get_template_dir() {
		return apply_filters( 'dwqa_get_template_dir', 'dwqa-templates/' );
	}

	public function load_template( $name, $extend = false, $include = true ) {
		if ( $extend ) {
			$name .= '-' . $extend;
		}

		$template = false;
		$template_dir = array(
			DWQA_STYLESHEET_DIR . $this->get_template_dir(),
			DWQA_TEMP_DIR . $this->get_template_dir(),
			DWQA_DIR . 'templates/'
		);

		foreach( $template_dir as $temp_path ) {
			if ( file_exists( $temp_path . $name . '.php' ) ) {
				$template = $temp_path . $name . '.php';
				break;
			}
		}

		$template = apply_filters( 'dwqa-load-template', $template, $name );

		if ( !$template || !file_exists( $template ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( "<strong>%s</strong> does not exists in <code>%s</code>.", $name, $template ), '1.4.0' );
			return false;
		}

		if ( ! $include ) {
			return $template;
		}
		include $template;
	}
}

function dwqa_get_mail_template( $option, $name = '' ) {
	if ( ! $name ) {
		return '';
	}
	$template = get_option( $option );
	if ( $template ) {
		return $template;
	} else {
		if ( file_exists( DWQA_DIR . 'templates/email/'.$name.'.html' ) ) {
			ob_start();
			load_template( DWQA_DIR . 'templates/email/'.$name.'.html', false );
			$template = ob_get_contents();
			ob_end_clean();
			return $template;
		} else {
			return '';
		}
	}
}

function dwqa_vote_best_answer_button() {
	global $current_user;
	$question_id = dwqa_get_post_parent_id( get_the_ID() );
	$question = get_post( $question_id );
		$best_answer = dwqa_get_the_best_answer( $question_id );
		$data = is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ? 'data-answer="'.get_the_ID().'" data-nonce="'.wp_create_nonce( '_dwqa_vote_best_answer' ).'" data-ajax="true"' : 'data-ajax="false"';
	if ( get_post_status( get_the_ID() ) != 'publish' ) {
		return false;
	}
	if ( $best_answer == get_the_ID() || ( is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ) ) {
		?>
		<div class="entry-vote-best <?php echo $best_answer == get_the_ID() ? 'active' : ''; ?>" <?php echo $data ?> >
			<a href="javascript:void( 0 );" title="<?php _e( 'Choose as the best answer','dw-question-answer' ) ?>">
				<div class="entry-vote-best-bg"></div>
				<i class="icon-thumbs-up"></i>
			</a>
		</div>
		<?php
	}
}
