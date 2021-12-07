<?php

require_once( 'block-voting.php' );

function emuzone_plugin_block_section_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	// Get "Section"
	$emulators = get_field('emulator');
	if ( is_array( $emulators ) && ( count( $emulators ) > 0 ) )
		emuzone_plugin_block_section_template( $emulators );
	else
		echo '<div class="alert alert-danger" role="alert">No emulator pages associated with section</div>';
}

function emuzone_plugin_block_section_template( array $emulators ) {
	?>
	<div class="row justify-content-center">
		<div class="col-xl-11">
			<table class="section">
				<?php emuzone_plugin_block_section_loop( $emulators ); ?>
				<tr class="legend">
					<td colspan="4"><i class="fa fa-check-circle-o text-success"></i> recommended</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

function emuzone_plugin_block_section_loop( array $emulators ) {
	foreach ($emulators as $emulator)
	{
		// URL is post/page permalink
		$url = get_permalink( $emulator->ID );
		// If name field is blank, use page title
		$name = strval( get_field( 'emulator_name', $emulator->ID ) );
		if ( empty( $name ) )
			$name = get_the_title( $emulator->ID );
		// Is emulator recommended for this section?
		$emulator_recommended = get_field( 'emulator_recommended', $emulator->ID );
		$recommended = false;
		if ( is_array( $emulator_recommended ) && in_array( get_the_ID(), $emulator_recommended ) )
			$recommended = true;
		// Other fields
		$emulator_platform = get_field( 'emulator_platform', $emulator->ID );
		$platform = '';
		if ( !empty( $emulator_platform ) && is_array($emulator_platform) && ( count( $emulator_platform ) > 0 ) )
			$platform = implode( ' ', $emulator_platform );
		$license = get_field( 'emulator_license', $emulator->ID );
		$vote_id = strval( get_field( 'emulator_vote_id', $emulator->ID ) );
		$description = strval( get_field( 'emulator_description', $emulator->ID ) );
		emuzone_plugin_block_section_item( $url, $name, $recommended, $platform, $license, $vote_id, $description );
	}
}

function emuzone_plugin_block_section_item( string $url, string $name, bool $recommended = false,
	string $platform = '', string $license= '', string $vote_id = '', string $description = '' ) {
	?>
	<tr class="emulator">
		<td class="link"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a><?php if ( $recommended ) echo '<i class="fa fa-check-circle-o text-success"></i>'; ?></td>
		<td class="platform"><?php echo esc_html( $platform ); ?></td>
		<td class="d-none d-sm-table-cell"><?php echo esc_html( $license ); ?></td>
		<td class="d-none d-md-table-cell align-items-center"><?php emuzone_plugin_block_voting_display( (rand(1,100)/10), '' ); ?></td>
	</tr>
	<tr class="description">
		<td colspan="4"><?php echo esc_html( $description ); ?></td>
	</tr>
	<?php
}
