<?php 

class DWQA_Posts_Base {

	private $slug;
	private $labels;

	public function __construct( $slug, $labels ) {
		$this->slug = $slug;
		$this->labels = is_array( $labels ) ? $labels : array();

		// add posttype
		add_action( 'init', array( $this, 'register' ) );
		// Do any init by it self
		add_action( 'init', array( $this, 'init' ) );
		// Custom Admin List Table
		add_filter( 'manage_posts_columns', array( $this, 'columns_head' ) );
	}

	// Abstract, do all init actions for itself
	public function init(){}

	public function columns_head( $default ){ return $default; }

	public function get_slug() {
		return $this->slug;
	}

	public function get_name_labels() {
		return wp_parse_args( $this->labels, array(
			'plural' => __( 'DWQA Posts', 'dwqa' ),
			'singular' => __( 'DWQA Post', 'dwqa' ),
		) );
	}

	public function set_labels() {
		$names = $this->get_name_labels();

		return $labels = array(
			'name'                => $names['plural'],
			'singular_name'       => $names['singular'],
			'add_new'             => _x( 'Add New', 'dwqa', 'dwqa' ) . ' ' . $names['singular'],
			'add_new_item'        => __( 'Add New', 'dwqa' ) . ' ' . $names['singular'],
			'edit_item'           => __( 'Edit', 'dwqa' ) . ' ' . $names['singular'],
			'new_item'            => __( 'New', 'dwqa' ) . ' ' . $names['singular'],
			'view_item'           => __( 'View', 'dwqa' ) . ' ' . $names['singular'],
			'search_items'        => __( 'Search ', 'dwqa' ) . $names['plural'],
			'not_found'           => $names['plural'] . ' ' . __( 'not found', 'dwqa' ),
			'not_found_in_trash'  => $names['plural'] . ' ' . __( 'not found in Trash', 'dwqa' ),
			'parent_item_colon'   => __( 'Parent:', 'dwqa' ) . ' ' . $names['singular'],
			'menu_name'           => isset( $names['menu'] ) ? $names['menu'] : $names['plural'],
		);
	}

	public function register() {
		
		$args = array(
			'labels'              => array(),
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => null,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title', 'editor', 'author', 'thumbnail',
				'excerpt','custom-fields', 'trackbacks', 'comments',
				'revisions', 'page-attributes', 'post-formats'
			)
		);

		foreach ( $args as $key => $value ) {
			$method = 'set_' . $key;
			if ( method_exists( $this, $method ) ) {
				$args[$key] = call_user_func( array( $this, $method ) );
			}
		}

		register_post_type( $this->get_slug(), $args );
	}

	

}

?>