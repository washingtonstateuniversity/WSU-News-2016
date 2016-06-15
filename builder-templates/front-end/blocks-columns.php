<?php
global $ttfmake_section_data, $ttfmake_sections, $post;

// Default to sidebar right if a section type has not been specified.
$section_type = ( isset( $ttfmake_section_data['section-type'] ) ) ? $ttfmake_section_data['section-type'] : 'wsuwpblockssidebarright';

if ( 'wsuwpblockssidebarright' === $section_type || 'wsuwpblockssidebarleft' === $section_type || 'wsuwpblocksthirds' === $section_type ) {
	$section_layout = ( isset( $ttfmake_section_data['section-layout'] ) ) ? $ttfmake_section_data['section-layout'] : 'side-right';
} elseif ( 'wsuwpblockshalves' === $section_type ) {
	$section_layout = 'halves';
} elseif ( 'wsuwpblocksquarters' === $section_type ) {
	$section_layout = 'quarters';
} else {
	$section_layout = 'single';
}

// Provide a list matching the number of columns to the selected section type.
$section_type_columns = array(
	'wsuwpblockssidebarright' => 2,
	'wsuwpblockssidebarleft'  => 2,
	'wsuwpblocksthirds'       => 3,
	'wsuwpblockshalves'       => 2,
	'wsuwpblocksquarters'     => 4,
	'wsuwpblockssingle'       => 1,
);

// Retrieve data for the column being output.
$data_columns = spine_get_column_data( $ttfmake_section_data, $section_type_columns[ $section_type ] );

// Assume by default that the section has no wrapper.
$section_has_wrapper = false;

// Sections can have ids (provided by outside forces other than this theme), classes, and wrappers with classes.
$section_classes         = ( isset( $ttfmake_section_data['section-classes'] ) ) ? $ttfmake_section_data['section-classes'] : '';
$section_wrapper_classes = ( isset( $ttfmake_section_data['section-wrapper'] ) ) ? $ttfmake_section_data['section-wrapper'] : '';

// If a child theme or plugin has declared a section ID, we handle that.
// This may be supported in the parent theme one day.
$section_id  = ( isset( $ttfmake_section_data['section-id'] ) ) ? $ttfmake_section_data['section-id'] : '';

// If a background image has been assigned to the section, capture it for use.
if ( isset( $ttfmake_section_data['background-img'] ) && ! empty( $ttfmake_section_data['background-img'] ) ) {
	$section_background = $ttfmake_section_data['background-img'];
} else {
	$section_background = false;
}

// If a mobile background image has been assigned to the section, capture it. Fallback to the section background.
if ( isset( $ttfmake_section_data['background-mobile-img'] ) && ! empty( $ttfmake_section_data['background-mobile-img'] ) ) {
	$section_mobile_background = $ttfmake_section_data['background-mobile-img'];
} elseif ( $section_background ) {
	$section_mobile_background = $section_background;
} else {
	$section_mobile_background = false;
}

// If a section has wrapper classes assigned, assume it (obviously) needs a wrapper.
if ( '' !== $section_wrapper_classes ) {
	$section_has_wrapper = true;
}

// If a background image has been assigned, a wrapper is required.
if ( $section_background || $section_mobile_background ) {
	$section_has_wrapper = true;
	$section_wrapper_classes .= ' section-wrapper-has-background';
}

if ( $section_has_wrapper ) {
	$section_wrapper_html = '<div';

	if ( '' !== $section_id ) {
		$section_wrapper_html .= ' id="' . esc_attr( $section_id ) . '"';
	}

	$section_wrapper_html .= ' class="section-wrapper ' . esc_attr( $section_wrapper_classes ) . '"';

	if ( $section_background ) {
		$section_wrapper_html .= ' data-background="' . esc_url( $section_background ) . '"';
	}

	if ( $section_mobile_background ) {
		$section_wrapper_html .= ' data-background-mobile="' . esc_url( $section_mobile_background ) . '"';
	}

	$section_wrapper_html .= '>';

	echo $section_wrapper_html;

	// Reset section_id so that the default is built for the section.
	$section_id = '';
}

// If a section ID is not available for use, we build a default ID.
if ( '' === $section_id ) {
	$section_id = 'builder-section-' . esc_attr( $ttfmake_section_data['id'] );
} else {
	$section_id = sanitize_key( $section_id );
}
?>
	<section id="<?php echo esc_attr( $section_id ); ?>" class="row <?php echo esc_attr( $section_layout ); ?> <?php echo esc_attr( $section_classes ); ?>">
		<?php
		if ( ! empty( $data_columns ) ) {
			// We output the column's number as part of a class and need to track count.
			$column_count = array( 'one', 'two', 'three', 'four' );
			$count = 0;

			$blocks_page = $post;

			foreach ( $data_columns as $column ) {
				if ( isset( $column['column-background-image'] ) && ! empty( $column['column-background-image'] ) ) {
					$column_background = "background-image:url('" . esc_url( $column['column-background-image'] ) ."');";
				} else {
					$column_background = '';
				}
				?>
				<div style="<?php echo $column_background; ?>" class="column <?php echo $column_count[ $count ]; $count++; ?> <?php if ( isset( $column['column-classes'] ) ) : echo esc_attr( $column['column-classes'] ); endif; ?>">

					<?php if ( isset( $column['post-id'] ) && ! empty( $column['post-id'] ) ) : ?>
						<?php /* Not sure what we'll do here just yet. */ ?>
						<?php
						$post = get_post( $column['post-id'] );
						setup_postdata( $post );
						?>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div><?php the_excerpt(); ?></div>
					<?php endif; ?>

				</div>
			<?php
			}

			wp_reset_postdata();
			$post = $blocks_page;
		}
		?>
	</section>
<?php
if ( $section_has_wrapper ) {
	echo '</div>';
}
