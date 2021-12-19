<?php

/* SVG should be output only once per page, should be set to TRUE once output */
$emuzone_voting_svg_output = false;

function emuzone_voting_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	// Get Vote ID from block field (should be empty in most cases)
	$vote_id = get_field('vote_id');
	// Otherwise, get Vote ID from emulator data (on emulator pages)
	if ( empty( $vote_id ) )
		$vote_id = get_field( 'emulator_vote_id', $post_id );
	// If still empty, set it to a default value (this should never happen)
	if ( is_null( $vote_id ) )
		$vote_id = '_invalid_';
	echo emuzone_votebox( $vote_id );
}

function emuzone_votebox( string $vote_id ) {
	global $wp;
	$redirect = home_url( add_query_arg( array( $_GET ), $wp->request . '/'), 'relative' );
?>
<br/><div class="row g-0 justify-content-center votebox">
	<div class="col-xl-4 col-lg-5 col-md-6 col-sm-6">
		<h2 class="votedisplay">User Rating</h2>
		<div class="votedisplay">
			<?php echo emuzone_voting_display( emuzone_voting_rating( $vote_id ), emuzone_voting_count( $vote_id ), 'Rating' ); ?>
		</div>
	</div>
	<div class="col-xl-4 col-lg-5 col-md-6 col-sm-6">
		<h2 class="vote">Vote</h2>
		<div class="vote">
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="emuzone_voting">
				<input type="hidden" name="action" value="emuzone_voting_response">
				<input type="hidden" name="emuzone_voting_nonce" value="<?php echo wp_create_nonce( 'emuzone_voting_nonce' ); ?>" />
				<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect );  ?>">
				<label for="voteSelection">Rate it:</label>
				<input type="hidden" name="emulator" value="<?php echo esc_attr( $vote_id ); ?>">
				<select name="vote" size="1" id="voteSelection">
					<option value="0">Select...</option>
					<option value="1">1 (awful)</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4 (poor)</option>
					<option value="5">5</option>
					<option value="6">6 (average)</option>
					<option value="7">7</option>
					<option value="8">8 (good)</option>
					<option value="9">9</option>
					<option value="10">10 (excellent)</option>
				</select>
				<input type="submit" name="submit" value="Vote!">
			</form>
		</div>
	</div>
</div><br/>
<?php
}

function emuzone_voting_response() {
	// Tell browsers not to cache any response
	nocache_headers();
	// Verify nonce
	if ( !isset( $_POST[ 'emuzone_voting_nonce' ] ) || !wp_verify_nonce( $_POST[ 'emuzone_voting_nonce' ], 'emuzone_voting_nonce' ) )
	{
		http_response_code( 400 );
		die( '<h1>Bad Request</h2>Try reloading the page where you came from.' );
	}
	wp_safe_redirect( $_POST['redirect'] );
	exit();
}
// Process vote form for all users (privileged and non-privileged)
add_action( 'admin_post_emuzone_voting_response', 'emuzone_voting_response' );
add_action( 'admin_post_nopriv_emuzone_voting_response', 'emuzone_voting_response' );

function emuzone_voting_rating ( string $vote_id ) {
	global $wpdb;
	$result = $wpdb->get_var( $wpdb->prepare( 'SELECT AVG(rating) FROM '.$wpdb->prefix.'ezvotes WHERE emulator_id="%s" AND vote_date > ( NOW() - INTERVAL 3 YEAR )', $vote_id ) );
	return floatval( $result );
}

function emuzone_voting_count ( string $vote_id ) {
	global $wpdb;
	$result = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM '.$wpdb->prefix.'ezvotes WHERE emulator_id="%s" AND vote_date > ( NOW() - INTERVAL 3 YEAR )', $vote_id ) );
	return intval( $result );
}

function emuzone_voting_display( float $rating, int $count = null, string $prefix = '' ) {
	global $emuzone_voting_svg_output;
	$awards = ["0" => 0.5, "5.7" => 1, "6.1" => 1.5, "6.5" => 2, "6.9" => 2.5, "7.3" => 3, "7.7" => 3.5, "8.1" => 4, "8.5" => 4.5, "8.9" => 5];
	foreach ( $awards as $key => $value ) {
		if ( $rating >= floatval($key) ) {
			$stars = $value;
		}
	}
	?>
	<p class="voting align-items-center" aria-label="<?php echo $stars; ?> stars out of 5">
		<?php if ( !$emuzone_voting_svg_output ) { ?>
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
				$emuzone_voting_svg_output = true;
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
	echo '<span class="v-rating">' . sprintf( '%.1f', $rating ) . '</span>';
	if ( !is_null( $count ) )
		echo '<small class="v-count">(' . sprintf( '%d', $count ) . ' votes)</small>';
}
