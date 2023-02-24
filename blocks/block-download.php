<?php

require_once( plugin_dir_path( __DIR__ ) . 'emuzone-plugin.php' );
require_once( plugin_dir_path( __DIR__ ) . 'legacy-config.php' );

/**
 * Callback for downloads block. Displays downloads from database (or manual entry).
 *
 * @param $block
 * @param $content
 * @param $is_preview
 * @param $post_id
 *
 * @return void
 */
function emuzone_download_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$downloads = get_field( 'downloads' );
	if ( is_array( $downloads ) && ( count( $downloads ) > 0 ) ) {
		// Rebuild array for simplicity & filter out empty entries
		$dl = array();
		foreach( $downloads as $key=>$file ) {
			if ( !empty( $file['file_id'] ) )
				$dl[] = $file['file_id'];
		}

		if ( count($dl) > 0 ) {
			emuzone_download_template( $dl );
			return;
		}
	}
	// else
	echo '<div class="alert alert-danger" role="alert">No downloads specified</div>';
}

/**
 * Displays main structure of the downloads block
 *
 * @param array $downloads
 *
 * @return void
 */
function emuzone_download_template( array $downloads ) {
	?>
	<table class="dl">
		<tr>
			<th>File</th>
			<th>Platform</th>
			<th>License</th>
			<th>Date</th>
			<th>Size</th>
			<th></th>
		</tr>
		<?php emuzone_download_loop( $downloads ); ?>
	</table>
	<?php
}

/**
 * Displays the list of downloads
 *
 * @param array $downloads
 *
 * @return void
 */
function emuzone_download_loop( array $downloads ) {
	global $legacydb;
	emuzone_legacydb_connect();
	$platformtypes = array( 'N/A', 'DOS', 'Windows', 'Linux', 'Mac', 'Windows (64-bit)', 'Windows (32-bit)' );
	$licensetypes = array( 'N/A', 'Public Domain', 'Freeware', 'Shareware', 'Demo', 'Open-Source' );

	foreach($downloads as $file) {
		if ( strpos( $file, ';' ) !== false ) {
			// "Manual" entry
			$parts = explode( ';', $file );
			$url = ( isset( $parts[0] ) ? trim( strval ( $parts[0] ) ) : '' );
			$name = ( isset( $parts[1] ) ? trim( strval ( $parts[1] ) ) : '' );
			$version = ( isset( $parts[2] ) ? trim( strval ( $parts[2] ) ) : '' );
			$description = ( isset( $parts[3] ) ? trim( strval ( $parts[3] ) ) : '' );
			$platform = ( isset( $parts[4] ) ? trim( strval ( $parts[4] ) ) : '' );
			$license = ( isset( $parts[5] ) ? trim( strval ( $parts[5] ) ) : '' );
			$data = ( isset( $parts[6] ) ? trim( strval ( $parts[6] ) ) : '' );
			$size = ( isset( $parts[7] ) ? trim( strval ( $parts[7] ) ) : '' );
			$homepage = ( isset( $parts[8] ) ? trim( strval ( $parts[8] ) ) : '' );

			emuzone_download_item( '_blank', $url, $name, $version, $description, $platform, $license, $data, $size, $homepage );
		} else {
			// Get data from legacy database
			$data = $legacydb->get_row( $legacydb->prepare( 'SELECT * FROM ez_files WHERE handle="%s"' , $file ), ARRAY_A );

			// Should return associative array, otherwise just fail with error and do not continue
			if ( empty( $data ) or !is_array( $data ) ) {
				echo '<div class="alert alert-danger" role="alert">Query failed for download &quot;' . esc_html( $file ) . '&quot;</div>';
				return;
			}

			emuzone_download_item(
					'_top',
					strval( LEGACY_DOWNLOAD_URL . $data['pathinfo'] ),
					strval( $data['name'] ),
					strval( $data['version'] ),
					strval( $data['description'] ),
					$platformtypes[ $data['platform'] ],
					$licensetypes[ $data['license'] ],
					date( 'M j, Y', $data['dateline'] ),
					// Get real filesize with: @filesize( LEGACY_FILES_PATH . $data['pathinfo'] )
					// But it's faster to use database value
					strval( ( floor( intval( $data['size'] ) / 1024 ) + 1 ) ),
					strval( $data['homepage'] )
			);
		}
	}
}

/**
 * Displays single download item
 *
 * @param string $target
 * @param string $url
 * @param string $name
 * @param string $version
 * @param string $description
 * @param string $platform
 * @param string $license
 * @param string $date
 * @param string $size
 * @param string $homepage
 *
 * @return void
 */
function emuzone_download_item( string $target, string $url, string $name, string $version, string $description, string $platform, string $license, string $date, string $size, string $homepage ) {
	?>
	<tr>
		<td data-th="File">
			<a target="<?php echo esc_attr( $target ); ?>" href="<?php echo esc_url( $url ); ?>"><b><?php echo esc_html( $name . ( !empty ( $version ) ? ' ' . esc_html( $version ) : '') ); ?></b></a>
			<?php if ( !empty( $description ) ) echo ' <small>' . esc_html( $description ) . '</small>'; ?>
		</td>
		<td data-th="Platform"> <?php if ( !empty( $platform ) ) echo esc_html( $platform ); else echo '-'; ?> </td>
		<td data-th="License"> <?php if ( !empty( $license ) ) echo esc_html( $license ); else echo '-'; ?> </td>
		<td data-th="Date"> <?php if ( !empty( $date ) ) echo esc_html( $date ); else echo '-'; ?> </td>
		<td data-th="Size"> <?php if ( !empty( $size ) ) echo '<b>' . esc_html( $size ) . '</b> Kb.'; else echo '-'; ?> </td>
		<td data-th="Site">
			<?php
				if ( !empty( $homepage ) )
					echo '<a target="_blank" href="' . esc_url( $homepage ) .'" title="' . esc_attr( $name ) . ' homepage"><i class="fas fa-home"></i></a>';
				else
					echo '<i class="fas fa-home text-muted" title="Site not available"></i>';
			?>
		</td>
	</tr>
	<?php
}
