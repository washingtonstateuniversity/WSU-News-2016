<?php

class WSU_News_Layout_Builder {

	/**
	 * @var WSU_News_Layout_Builder
	 */
	private static $instance;

	/**
	 * @var array Holds all taxonomies registered for posts.
	 */
	public $post_taxonomies = array();

	/**
	 * Maintain and return the one instance and initiate hooks when
	 * called the first time.
	 *
	 * @return \WSU_News_Layout_Builder
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSU_News_Layout_Builder;
			self::$instance->load_walker();
			self::$instance->setup_hooks();
		}

		return self::$instance;
	}

	/**
	 * Load the walker class used by this plugin.
	 */
	public function load_walker() {
		require_once( dirname( __FILE__ ) . '/class-layout-builder-taxonomy-walker.php' );
	}

	/**
	 * Setup hooks for the plugin.
	 */
	public function setup_hooks() {
		add_filter( 'make_is_builder_page', array( $this, 'make_is_builder_page'), 10, 2 );
		add_action( 'admin_init', array( $this, 'set_post_taxonomies' ) );
		add_action( 'admin_init', array( $this, 'custom_builder_sections' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10 );
		add_action( 'add_meta_boxes_page', array( $this, 'add_meta_boxes' ), 10 );
		add_action( 'save_post_page', array( $this, 'save_post' ), 10, 2 );
		add_action( 'wp_ajax_set_layout_builder_items', array( $this, 'ajax_callback' ), 10 );
		add_action( 'wp_ajax_nopriv_set_layout_builder_items', array( $this, 'ajax_callback' ), 10 );
	}

	/**
	 * Set pages that have the Drag/Drop Builder Template to use the builder.
	 *
	 * @param bool    $is_builder_page    Whether or not the post uses the builder.
	 * @param int     $post_id            The ID of post being evaluated.
	 */
	public function make_is_builder_page( $is_builder_page, $post_id ) {
		if ( 'template-dragdrop.php' === get_page_template_slug( $post_id ) ) {
			$is_builder_page = true;
		}

		return $is_builder_page;
	}

	/**
	 * Set the `post_taxonomies` value via `get_object_taxonomies`.
	 * Unset 'post_format' as we likely won't be using it.
	 */
	public function set_post_taxonomies() {
		$this->post_taxonomies = get_object_taxonomies( 'post', 'object' );
		unset($this->post_taxonomies['post_format']);
	}

	/**
	 * Add custom sections used for the drag/drop implemenation of the page builder.
	 */
	public function custom_builder_sections() {
		ttfmake_add_section(
			'wsuwpdragdropsingle',
			'Single',
			get_template_directory_uri() . '/inc/builder/sections/css/images/blank.png',
			'A single column.',
			array( $this, 'save_columns' ),
			'admin/dragdrop-columns',
			'front-end/dragdrop-columns',
			710,
			'builder-templates/'
		);
		ttfmake_add_section(
			'wsuwpdragdropsidebarleft',
			'Sidebar Left',
			get_template_directory_uri() . '/inc/builder-custom/images/side-left.png',
			'Two column layout with the right side larger than the left.',
			array( $this, 'save_columns' ),
			'admin/dragdrop-columns',
			'front-end/dragdrop-columns',
			720,
			'builder-templates/'
		);
		ttfmake_add_section(
			'wsuwpdragdropsidebarright',
			'Sidebar Right',
			get_template_directory_uri() . '/inc/builder-custom/images/side-right.png',
			'Two column layout with the left side larger than the right.',
			array( $this, 'save_columns' ),
			'admin/dragdrop-columns',
			'front-end/dragdrop-columns',
			730,
			'builder-templates/'
		);
		ttfmake_add_section(
			'wsuwpdragdrophalves',
			'Halves',
			get_template_directory_uri() . '/inc/builder-custom/images/halves.png',
			'Two equal columns.',
			array( $this, 'save_columns' ),
			'admin/dragdrop-columns',
			'front-end/dragdrop-columns',
			740,
			'builder-templates/'
		);
		ttfmake_add_section(
			'wsuwpdragdropthirds',
			'Thirds',
			get_template_directory_uri() . '/inc/builder-custom/images/thirds.png',
			'Three column layout, choose between thirds and triptych.',
			array( $this, 'save_columns' ),
			'admin/dragdrop-columns',
			'front-end/dragdrop-columns',
			750,
			'builder-templates/'
		);
		ttfmake_add_section(
			'wsuwpdragdropquarters',
			'Quarters',
			get_template_directory_uri() . '/inc/builder-custom/images/quarters.png',
			'Four column layout, all equal sizes.',
			array( $this, 'save_columns' ),
			'admin/dragdrop-columns',
			'front-end/dragdrop-columns',
			760,
			'builder-templates/'
		);
	}

	/**
	 * Clean the data being passed from the save of a columns layout.
	 *
	 * @param array $data Array of data inputs being passed.
	 *
	 * @return array Clean data.
	 */
	public function save_columns( $data ) {
		$clean_data = array();
		if ( isset( $data['columns-number'] ) ) {
			if ( in_array( $data['columns-number'], range( 1, 4 ) ) ) {
				$clean_data['columns-number'] = $data['columns-number'];
			}
		}
		if ( isset( $data['columns-order'] ) ) {
			$clean_data['columns-order'] = array_map( array( 'TTFMake_Builder_Save', 'clean_section_id' ), explode( ',', $data['columns-order'] ) );
		}
		if ( isset( $data['columns'] ) && is_array( $data['columns'] ) ) {
			$i = 1;
			foreach ( $data['columns'] as $id => $item ) {
				if ( isset( $item['toggle'] ) ) {
					if ( in_array( $item['toggle'], array( 'visible', 'invisible' ) ) ) {
						$clean_data['columns'][ $id ]['toggle'] = $item['toggle'];
					}
				}
				if ( isset( $item['post-id'] ) ) {
					$clean_data['columns'][ $id ]['post-id'] = sanitize_text_field( $item['post-id'] );
				}
				$i++;
			}
		}
		if ( isset( $data['label'] ) ) {
			$clean_data['label'] = sanitize_text_field( $data['label'] );
		}
		$clean_data = apply_filters( 'spine_builder_save_columns', $clean_data, $data );
		return $clean_data;
	}

	/**
	 * Enqueue the scripts used for layout building.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'page' !== get_current_screen()->id ) {
			return;
		}

		wp_enqueue_script( 'wsuwp-layout-builder', get_stylesheet_directory_uri() . '/js/layout-builder-admin.js', array( 'jquery-ui-draggable', 'jquery-ui-sortable', 'ttfmake-admin-edit-page' ), false, true );
		wp_enqueue_style(  'wsuwp-layout-builder', get_stylesheet_directory_uri() . '/css/layout-builder-admin.css', array( 'ttfmake-builder' ) );
	}

	/**
	 * Add the meta box used for staging content items, positioned beneath the Publish box.
	 */
	public function add_meta_boxes() {
		remove_meta_box( 'submitdiv', 'page', 'side' );

		add_meta_box(
			'wsuwp-builder-content',
			'Content Items',
			array( $this, 'display_builder_content_meta_box' ),
			'page',
			'side',
			'high'
		);

		add_meta_box( 'submitdiv', __( 'Publish' ), 'post_submit_meta_box', 'page', 'side', 'high' );
	}

	/**
	 * Display a staging area for loading content items.
	 *
	 * @param WP_Post $post Object of the current post being edited.
	 */
	public function display_builder_content_meta_box( $post ) {
		wp_nonce_field( 'save-wsuwp-layout-build', '_wsuwp_layout_build_nonce' );

		$localized_data = array( 'post_id' => $post->ID );

		$staged_items = '';

		$relation_meta = get_post_meta( $post->ID, '_wsuwp_layout_builder_term_relation', true );

		$relation = ( $relation_meta ) ? $relation_meta : 'OR';

		// If this page already has content loaded, we want to make it available to the JS.
		if ( $post_ids = get_post_meta( $post->ID, '_wsuwp_layout_builder_staged_items', true ) ) {
			$localized_data['items'] = $this->_build_layout_items_response( $post_ids );
			$staged_items = implode( ',', $post_ids );
		}

		wp_localize_script( 'wsuwp-layout-builder', 'wsuwp_layout_build', $localized_data );

		foreach ( $this->post_taxonomies as $taxonomy ) {
			$selected_terms = '';

			if ( $terms = get_post_meta( $post->ID, '_wsuwp_layout_builder_' . $taxonomy->name . '_terms', true ) ) {
				$selected_terms = $terms;
			}
			?>

			<div class="wsuwp-layout-builder-terms closed">

				<button type="button" class="handlediv button-link" aria-expanded="true">
					<span class="screen-reader-text">Toggle panel: <?php echo $taxonomy->label; ?> terms</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
				<p><?php echo $taxonomy->label; ?></p>

				<div>
					<input type="search" value="" placeholder="Quick search" autocomplete="off" class="widefat">
					<ul class="categorychecklist">
					<?php
					wp_terms_checklist( null, array(
						'selected_cats' => $selected_terms,
						'walker'        => new WSUWP_Layout_Builder_Taxonomy_Options_Walker(),
						'taxonomy'      => $taxonomy->name,
					) );
					?>
					</ul>
				</div>
			</div>
			<?php
		} ?>
		<p class="wsuwp-builder-term-relation">Get posts with<br />
			<input type="radio" name="wsuwp_layout_builder_term_relation" value="OR"<?php checked( $relation, 'OR' ); ?> />any<br />
			<input type="radio" name="wsuwp_layout_builder_term_relation" value="AND"<?php checked( $relation, 'AND' ); ?>/>all<br />
			of the selected terms</p>
		<div class="wsuwp-builder-load-button-wrap">
			<input type="button" value="Load Items" id="wsuwp-builder-load-items" class="button button-large button-secondary" />
		</div>
		<div id="wsuwp-layout-builder-items" class="wsuwp-spine-builder-column"></div>
		<input type="hidden" id="wsuwp-layout-builder-staged-items" name="wsuwp_layout_builder_staged_items" value="<?php echo esc_attr( $staged_items ); ?>" />
		<?php
	}

	/**
	 * Capture the data associated with the layout build.
	 *
	 * @param int     $post_id ID of the current post being saved.
	 * @param WP_Post $post    Object of the current post being saved.
	 */
	public function save_post( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['_wsuwp_layout_build_nonce'] ) || ! wp_verify_nonce( $_POST['_wsuwp_layout_build_nonce'], 'save-wsuwp-layout-build' ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( 'page' !== $post->post_type ) {
			return;
		}

		if ( ! empty( $_POST['wsuwp_layout_builder_staged_items'] ) ) {
			$issue_staged_items = explode( ',', $_POST['wsuwp_layout_builder_staged_items'] );
			$issue_staged_items = array_map( 'absint', $issue_staged_items );
			update_post_meta( $post_id, '_wsuwp_layout_builder_staged_items', $issue_staged_items );
		} else {
			delete_post_meta( $post_id, '_wsuwp_layout_builder_staged_items' );
		}

		foreach ( $this->post_taxonomies as $taxonomy ) {
			if ( ! empty( $_POST['wsuwp_layout_builder_' . $taxonomy->name . '_terms']) ) {
				$selected_terms = array_map( 'absint', $_POST['wsuwp_layout_builder_' . $taxonomy->name . '_terms'] );
				update_post_meta( $post_id, '_wsuwp_layout_builder_' . $taxonomy->name . '_terms', $selected_terms );
			} else {
				delete_post_meta( $post_id, '_wsuwp_layout_builder_' . $taxonomy->name . '_terms' );
			}
		}

		$relation = $_POST['wsuwp_layout_builder_term_relation'];
		if ( isset( $relation ) && ( 'OR' === $relation || 'AND' === $relation ) ) {
			update_post_meta( $post_id, '_wsuwp_layout_builder_term_relation', $relation );
		} else {
			delete_post_meta( $post_id, '_wsuwp_layout_builder_term_relation' );
		}
	}

	/**
	 * Build the list of content items based on the selected options.
	 *
	 * @param array         $post_ids    List of specific post IDs to include. Defaults to an empty array.
	 * @param array         $category    Category terms to query. Defaults to an empty array.
	 * @param array         $tag         Tag terms to query. Defaults to an empty array.
	 * @param array         $u_category  University Category terms to query. Defaults to an empty array.
	 * @param array         $location    University Location terms to query. Defaults to an empty array.
	 * @param array         $org         University Organization terms to query. Defaults to an empty array.
	 * @param null|string   $relation    Taxonomy query relation. Null indicates none.
	 *
	 * @return array Containing information on each issue article.
	 */
	private function _build_layout_items_response( $post_ids = array(), $category = array(), $tag = array(), $u_category = array(), $location = array(), $org = array(), $relation = null ) {
		$query_args = array(
			'post_type'      => 'post',
			'posts_per_page' => 10,
		);

		// If an array of post IDs has been passed, use only those.
		if ( ! empty( $post_ids ) ) {
			$query_args['post__in'] = $post_ids;
			$query_args['orderby']  = 'post__in';
		}

		// Taxonomy options.
		if ( $category || $tag || $u_category || $location || $org ) {
			$query_args['tax_query'] = array();

			if ( $relation ) {
				$query_args['tax_query'] = array(
					'relation' => $relation,
				);
			}

			$operator = ( $relation && 'AND' === $relation ) ? 'AND' : 'IN';

			if ( $category ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $category,
					'operator' => $operator,
				);
			}

			if ( $tag ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag,
					'operator' => $operator,
				);
			}

			if ( $u_category ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'wsuwp_university_category',
					'field'    => 'term_id',
					'terms'    => $tag,
					'operator' => $operator,
				);
			}

			if ( $location ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'wsuwp_university_location',
					'terms'    => $tag,
					'operator' => $operator,
				);
			}

			if ( $org ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'wsuwp_university_org',
					'field'    => 'term_id',
					'terms'    => $tag,
					'operator' => $operator,
				);
			}
		}

		$items = array();
		$query = get_posts( $query_args );
		foreach ( $query as $post ) {
			$items[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'excerpt' => $post->post_excerpt,
			);
		}
		wp_reset_postdata();

		return $items;
	}

	/**
	 * Handle the ajax callback to push a list of items to a page.
	 */
	public function ajax_callback() {
		if ( ! DOING_AJAX || ! isset( $_POST['action'] ) || 'set_layout_builder_items' !== $_POST['action'] ) {
			die();
		}

		if ( isset( $_POST['post_ids'] ) ) {
			$post_ids = explode( ',', $_POST['post_ids'] );
		} else {
			$post_ids = array();
		}

		if ( isset( $_POST['category'] ) ) {
			$category = array_map( 'absint', $_POST['category'] );
		} else {
			$category = array();
		}

		if ( isset( $_POST['tag'] ) ) {
			$tag = array_map( 'absint', $_POST['tag'] );
		} else {
			$tag = array();
		}

		if ( isset( $_POST['u_category'] ) ) {
			$u_category = array_map( 'absint', $_POST['u_category'] );
		} else {
			$u_category = array();
		}

		if ( isset( $_POST['location'] ) ) {
			$location = array_map( 'absint', $_POST['location'] );
		} else {
			$location = array();
		}

		if ( isset( $_POST['organization'] ) ) {
			$organization = array_map( 'absint', $_POST['organization'] );
		} else {
			$organization = array();
		}

		if ( isset( $_POST['relation'] ) && ( 'OR' === $_POST['relation'] || 'AND' === $_POST['relation'] ) ) {
			$relation = $_POST['relation'];
		} else {
			$relation = false;
		}

		echo json_encode( $this->_build_layout_items_response( $post_ids, $category, $tag, $u_category, $location, $organization, $relation ) );

		exit();
	}

}

add_action( 'after_setup_theme', 'WSU_News_Layout_Builder', 11 );
/**
 * Start things up.
 *
 * @return \WSU_News_Layout_Builder
 */
function WSU_News_Layout_Builder() {
	return WSU_News_Layout_Builder::get_instance();
}
