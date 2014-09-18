<?php  

class Walker_Category_DWQA extends Walker_Category {
	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int $depth Depth of category in reference to parents.
	 * @param array $args
	 */
	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		extract( $args );

		$cat_name = esc_attr( $category->name );
		$cat_name = apply_filters( 'list_cats', $cat_name, $category );
		$link = '<a href="' . esc_url( get_term_link( $category ) ) . '" ';
		if ( $use_desc_for_title == 0 || empty( $category->description ) )
			$link .= 'title="' . esc_attr( sprintf( __( 'View all posts filed under %s' ), $cat_name ) ) . '"';
		else
			$link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
		$link .= '>';
		$link .= $cat_name . '</a>';

		if ( ! empty( $feed_image ) || ! empty( $feed ) ) {
			$link .= ' ';

			if ( empty( $feed_image ) )
				$link .= '( ';

			$link .= '<a href="' . esc_url( get_term_feed_link( $category->term_id, $category->taxonomy, $feed_type ) ) . '"';

			if ( empty( $feed ) ) {
				$alt = ' alt="' . sprintf( __( 'Feed for all posts filed under %s' ), $cat_name ) . '"';
			} else {
				$title = ' title="' . $feed . '"';
				$alt = ' alt="' . $feed . '"';
				$name = $feed;
				$link .= $title;
			}

			$link .= '>';

			if ( empty( $feed_image ) )
				$link .= $name;
			else
				$link .= "<img src='$feed_image'$alt$title" . ' />';

			$link .= '</a>';

			if ( empty( $feed_image ) )
				$link .= ' )';
		}

		if ( ! empty( $show_count ) )
			$link .= ' (' . intval( $category->count ) . ' )';

		if ( 'list' == $args['style'] ) {
			$output .= "\t<li";
			$class = 'cat-item cat-item-' . $category->term_id;
			if ( ! empty( $current_category ) ) {
				$_current_category = get_term( $current_category, $category->taxonomy );
				if ( $category->term_id == $current_category )
					$class .= ' current-cat';
				elseif ( $category->term_id == $_current_category->parent )
					$class .= ' current-cat-parent';
			}
			$output .= ' class="' . $class . '"';
			$output .= ' data-cat="'.$category->term_id.'"';
			$output .= ">$link\n";
		} else {
			$output .= "\t$link<br />\n";
		}
	}
}
?>