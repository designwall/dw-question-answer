<?php 

global $dwqa_options, $wpdb;
$taxonomy = get_query_var( 'taxonomy' );
$term_name = get_query_var( $taxonomy );


if ( function_exists('dwqa_table_exists') && dwqa_table_exists( $wpdb->prefix . 'dwqa_question_index' ) ) {
	// Page navigation
	$total = wp_cache_get( 'dwqa_total_questions_new_table', 'dwqa' );
	if ( ! $total ) {

		$sticky_questions = get_option( 'dwqa_sticky_questions', array() );
		$where = ' WHERE 1=1';
		if ( ! empty( $sticky_questions ) ) {
			$where .= " AND ID NOT IN ( " . implode( ',', $sticky_questions ) . " )";
		}
		$query = "SELECT count(*) FROM ".($wpdb->prefix . 'dwqa_question_index')." " . $where;
		$total = $wpdb->get_var( $query  );
		wp_cache_add( 'dwqa_total_questions_new_table', $total, 'dwqa' );
	}
} else {
	if ( $taxonomy && $term_name ) {
		$term = get_term_by( 'slug', $term_name, $taxonomy );
		$total = $term->count;
	} else {
		$post_count = wp_count_posts( 'dwqa-question' );
		$total = $post_count->publish;
		if ( current_user_can( 'manage_options' ) ) {
			$total += $post_count->private;
		}
	}
}

$number_questions = $total;

$number = isset( $dwqa_options[ 'posts-per-page' ] ) ? $dwqa_options[ 'posts-per-page' ] : 5;

$pages = ceil( $number_questions / $number );

if ( $pages > 1 ) :
	echo '<div class="pagination">';
	echo '<ul data-pages="'.$pages.'" >';

	$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
	$i = 0;
	echo '<li class="prev';
	if ( $i == 0 ) {
		echo ' dwqa-hide';
	}
	echo '"><a href="javascript:void()">'.__( 'Prev', 'dwqa' ).'</a></li>';
	$link = get_permalink( $dwqa_options['pages']['archive-question'] );
	$start = $paged - 2;
	$end = $paged + 2;

	if ( $end > $pages ) {
		$end = $pages;
		$start = $pages -  5;
	}

	if ( $start < 1 ) {
		$start = 1;
		$end = 5;
		if ( $end > $pages ) {
			$end = $pages;
		}
	}
	if ( $start > 1 ) {
		echo '<li><a href="'.esc_url(add_query_arg( 'paged',1,$link ) ).'">1</a></li><li class="dot"><span>...</span></li>';
	}
	for ( $i = $start; $i <= $end; $i++ ) { 
		$current = $i == $paged ? 'class="active"' : '';
		if ( $i == 1 ) {
			echo '<li '.$current.'><a href="'.$link.'">'.$i.'</a></li>';
		} else {
			echo '<li '.$current.'><a href="'.esc_url( add_query_arg( 'paged', $i, $link ) ).'">'.$i.'</a></li>';
		}
	}

	if ( $i - 1 < $pages ) {
		echo '<li class="dot"><span>...</span></li><li><a href="'.esc_url(add_query_arg( 'paged', $pages, $link ) ).'">'.$pages.'</a></li>';
	}
	echo '<li class="next';
	if ( $paged == $pages ) {
		echo ' dwqa-hide';
	}
	echo '"><a href="javascript:void()">'.__( 'Next', 'dwqa' ) .'</a></li>';
	echo '</ul>';
	echo '</div>';

endif;
