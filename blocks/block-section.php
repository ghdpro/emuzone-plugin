<?php

require_once( 'block-voting.php' );

/**
 * Callback for the section block. Displays sorted list of emulator (pages) based on ACF data.
 *
 * @param $block
 * @param $content
 * @param $is_preview
 * @param $post_id
 *
 * @return void
 */
function emuzone_section_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	// Get "Section"
	$emulators = get_field('emulator');
	if ( is_array( $emulators ) && ( count( $emulators ) > 0 ) )
		emuzone_section_template( $emulators );
	else
		echo '<div class="alert alert-danger" role="alert">No emulator pages associated with section</div>';
}

/**
 * Displays main structure of the section block
 *
 * @param array $emulators
 *
 * @return void
 */
function emuzone_section_template( array $emulators ) {
	?>
	<div class="row justify-content-center">
		<div class="col-xl-11">
			<table class="section">
				<?php emuzone_section_loop( $emulators ); ?>
				<tr class="legend">
					<td colspan="4"><i class="fa fa-check-circle-o text-success"></i> recommended</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Displays the list of emulators
 *
 * @param array $emulators
 *
 * @return void
 */
function emuzone_section_loop( array $emulators ) {
	// Build sorted list of rating (descending order), maintaining same key as $emulators array
	$ratings = array();
	foreach ( $emulators as $key=>$emulator ) {
		$vote_id = strval( get_field( 'emulator_vote_id', $emulator->ID ) );
		$ratings[$key] = emuzone_voting_rating( $vote_id );
	}
	arsort( $ratings );
	// Show each item in $emulators in the order obtained from $ratings
	foreach ( $ratings as $key=>$rating ) {
		// URL is post/page permalink
		$url = get_permalink( $emulators[$key]->ID );
		// If name field is blank, use page title
		$name = strval( get_field( 'emulator_name', $emulators[$key]->ID ) );
		if ( empty( $name ) )
			$name = get_the_title( $emulators[$key]->ID );
		// Is emulator recommended for this section?
		$emulator_recommended = get_field( 'emulator_recommended', $emulators[$key]->ID );
		$recommended = false;
		if ( is_array( $emulator_recommended ) && in_array( get_the_ID(), $emulator_recommended ) )
			$recommended = true;
		// Other fields
		$emulator_platform = get_field( 'emulator_platform', $emulators[$key]->ID );
		$platform = '';
		if ( !empty( $emulator_platform ) && is_array($emulator_platform) && ( count( $emulator_platform ) > 0 ) )
			$platform = implode( ' ', $emulator_platform );
		$license = strval ( get_field( 'emulator_license', $emulators[$key]->ID ) );
		$description = strval( get_field( 'emulator_description', $emulators[$key]->ID ) );
		emuzone_section_item( $url, $name, $recommended, $platform, $license, $rating, $description );
	}
}

/**
 * Displays single emulator item
 *
 * @param string $url
 * @param string $name
 * @param bool $recommended
 * @param string $platform
 * @param string $license
 * @param float $rating
 * @param string $description
 *
 * @return void
 */
function emuzone_section_item( string $url, string $name, bool $recommended = false, string $platform = '', string $license= '', float $rating = 0, string $description = '' ) {
	?>
	<tr class="emulator">
		<td class="link"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a><?php if ( $recommended ) echo '<i class="fa fa-check-circle-o text-success"></i>'; ?></td>
		<td class="platform"><?php echo esc_html( $platform ); ?></td>
		<td class="d-none d-md-table-cell"><?php echo esc_html( $license ); ?></td>
		<td class="d-none d-lg-table-cell align-items-center"><?php emuzone_voting_display( $rating ); ?></td>
	</tr>
	<tr class="description">
		<td colspan="4"><?php echo esc_html( $description ); ?>&nbsp;</td>
	</tr>
	<?php
}
