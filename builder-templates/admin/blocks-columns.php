<?php

spine_load_section_header();

global $ttfmake_section_data, $ttfmake_is_js_template;

if ( in_array( $ttfmake_section_data['section']['id'], array( 'wsuwpblockshalves', 'wsuwpblockssidebarright', 'wsuwpblockssidebarleft' ) ) ) {
	$wsuwp_range = 2;
} elseif ( 'wsuwpblocksthirds' === $ttfmake_section_data['section']['id'] ) {
	$wsuwp_range = 3;
} elseif ( 'wsuwpblocksquarters' === $ttfmake_section_data['section']['id'] ) {
	$wsuwp_range = 4;
} else {
	$wsuwp_range = 1;
}

$section_name   = ttfmake_get_section_name( $ttfmake_section_data, $ttfmake_is_js_template );
$section_order  = ( ! empty( $ttfmake_section_data['data']['columns-order'] ) ) ? $ttfmake_section_data['data']['columns-order'] : range( 1, $wsuwp_range );

?>
	<div class="wsuwp-spine-blocks-stage">
		<?php $j = 1; foreach ( $section_order as $key => $i ) : ?>
			<?php
			$column_name = $section_name . '[columns][' . $i . ']';
			$post_id     = ( isset( $ttfmake_section_data['data']['columns'][ $i ]['post-id'] ) ) ? $ttfmake_section_data['data']['columns'][ $i ]['post-id'] : '';
			$visible     = ( isset( $ttfmake_section_data['data']['columns'][ $i ]['toggle'] ) ) ? $ttfmake_section_data['data']['columns'][ $i ]['toggle'] : 'visible';

			if ( ! in_array( $visible, array( 'visible', 'invisible' ) ) ) {
				$visible = 'visible';
			}

			if ( 'invisible' === $visible ) {
				$column_style = 'style="display: none;"';
				$toggle_class = 'wsuwp-toggle-closed';
			} else {
				$column_style = '';
				$toggle_class = '';
			}
			?>
			<div class="wsuwp-spine-builder-column wsuwp-spine-builder-column-position-<?php echo $j; ?>" data-id="<?php echo $i; ?>">
				<input type="hidden" class="wsuwp-column-visible" name="<?php echo $column_name; ?>[toggle]" value="<?php echo $visible; ?>" />
				<input type="hidden" class="wsuwp-column-post-id" name="<?php echo $column_name; ?>[post-id]" value="<?php echo $post_id; ?>" aria-hidden="true" />
				<div class="spine-builder-column-overlay">
					<div class="spine-builder-column-overlay-wrapper">
						<div class="spine-builder-overlay-header">
							<div class="spine-builder-overlay-title">Configure Column</div>
							<div class="spine-builder-column-overlay-close">Done</div>
						</div>
						<div class="spine-builder-overlay-body">
							<?php spine_output_builder_column_classes( $column_name, $ttfmake_section_data, $j ); ?>
						</div>
					</div>
				</div>
				<?php if ( $post_id ) : ?>
				<div id="wsuwp-blocks-item-<?php echo esc_html( $post_id ); ?>" class="wsuwp-blocks-item">
						<div class="ttfmake-sortable-handle" title="Drag-and-drop this post into place">
						<a href="#" class="spine-builder-column-configure"><span>Configure</span></a>
						<a href="#" class="ttfmake-builder-section-footer-link blocks-item-remove"><span>Remove</span></a>
						<a href="#" class="wsuwp-column-toggle" title="Click to toggle"><div class="handlediv"></div></a>
						<div class="wsuwp-builder-column-title"><?php echo get_the_title( esc_html( $post_id ) ); ?></div>
					</div>
					<div class="wsuwp-blocks-item-body wsuwp-column-content">
						<h2><?php echo get_the_title( esc_html( $post_id ) ); ?></h2>
						<div class="wsuwp-blocks-item-excerpt"><?php echo get_the_excerpt( esc_html( $post_id ) ); ?></div>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<?php
			$j++;
		endforeach; ?>
	</div>

	<div class="clear"></div>
	<div class="spine-builder-overlay">
		<div class="spine-builder-overlay-wrapper">
			<div class="spine-builder-overlay-header">
				<div class="spine-builder-overlay-title">Configure Section</div>
				<div class="spine-builder-overlay-close">Done</div>
			</div>
			<div class="spine-builder-overlay-body">
				<?php
				spine_output_builder_section_layout( $section_name, $ttfmake_section_data );
				spine_output_builder_section_classes( $section_name, $ttfmake_section_data );
				spine_output_builder_section_wrapper( $section_name, $ttfmake_section_data );
				spine_output_builder_section_label( $section_name, $ttfmake_section_data );
				spine_output_builder_section_background( $section_name, $ttfmake_section_data );

				do_action( 'spine_output_builder_section', $section_name, $ttfmake_section_data, 'columns' );
				?>
			</div>
		</div>
	</div>
	<input type="hidden" value="<?php echo esc_attr( implode( ',', $section_order ) ); ?>" name="<?php echo $section_name; ?>[columns-order]" class="wsuwp-spine-builder-columns-order" />
	<input type="hidden" class="ttfmake-section-state" name="<?php echo $section_name; ?>[state]" value="<?php
	if ( isset( $ttfmake_section_data['data']['state'] ) ) {
		echo esc_attr( $ttfmake_section_data['data']['state'] );
	} else {
		echo 'open';
	} ?>" />
<?php
spine_load_section_footer();
