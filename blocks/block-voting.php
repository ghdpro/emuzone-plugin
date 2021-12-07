<?php

/* SVG should be output only once per page, should be set to TRUE once output */
$emuzone_plugin_block_voting_svg_output = false;

function emuzone_plugin_block_voting_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	echo '<div>'.get_field('vote_id') . $content .' ['. date( 'U' ).'] '. get_field( 'emulator_vote_id', $post_id ) .  '</div>';
}

function emuzone_plugin_block_voting_display( float $rating, string $prefix = 'Rating:' ) {
	global $emuzone_plugin_block_voting_svg_output;
	$awards = ["0" => 0.5, "5.7" => 1, "6.1" => 1.5, "6.5" => 2, "6.9" => 2.5, "7.3" => 3, "7.7" => 3.5, "8.1" => 4, "8.5" => 4.5, "8.9" => 5];
	foreach ( $awards as $key => $value ) {
		if ( $rating >= floatval($key) ) {
			$stars = $value;
		}
	}
	?>
	<p class="voting align-items-center" aria-label="<?php echo $stars; ?> stars out of 5">
		<?php if ( !$emuzone_plugin_block_voting_svg_output ) { ?>
		<svg width="0" height="0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
			<defs>
				<linearGradient id="half" x1="0" x2="100%" y1="0" y2="0">
					<stop offset="50%" stop-color="#FED94B"></stop>
					<stop offset="50%" stop-color="#F7F0C3"></stop>
				</linearGradient>
				<symbol xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" id="star">
					<path d="M31.547 12a.848.848 0 00-.677-.577l-9.427-1.376-4.224-8.532a.847.847 0 00-1.516 0l-4.218 8.534-9.427 1.355a.847.847 0 00-.467 1.467l6.823 6.664-1.612 9.375a.847.847 0 001.23.893l8.428-4.434 8.432 4.432a.847.847 0 001.229-.894l-1.615-9.373 6.822-6.665a.845.845 0 00.214-.869z" />
				</symbol>
			</defs>
		</svg>
		<?php
				$emuzone_plugin_block_voting_svg_output = true;
			}
		?>
	<?php
	echo $prefix;
	for ( $i = 1; $i <= 5; $i++ ) {
		if ( $i <= $stars ) {
			?>
			<svg class="v-star active" width="16" height="16" viewBox="0 0 32 32">
				<use xlink:href="#star"></use>
			</svg>
			<?php
		} elseif ( $i <= ( $stars + 0.5 ) ) {
			?>
			<svg class="v-star active" width="16" height="16" viewBox="0 0 32 32">
				<use xlink:href="#star" fill="url(#half)"></use>
			</svg>
			<?php
		} else {
			?>
			<svg class="v-star" width="16" height="16" viewBox="0 0 32 32">
				<use xlink:href="#star"></use>
			</svg>
			<?php
		}
	}
	echo '<span class="v-rating">' . $rating . '</span>';
}
