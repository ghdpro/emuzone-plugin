<?php
/**
 * Script for converting legacy emulator vote data to new format used by this plugin
 *
 * This script is intended to be run via the WP-CLI "eval-file" command, ie:
 *   wp eval-file vote-convert.php
 */

// Prevent invocation via browser
if ( php_sapi_name() != 'cli' )
	die('Error: this script is intended to be run from the command line.');

echo 'Converting legacy emulator vote data';

require_once( 'emuzone-plugin.php' );

global $wpdb;
global $legacydb;
emuzone_plugin_legacy_database_connect();

// wpdb class won't work that well for huge datasets, so using raw MySQLi statements here on database handle
$result = mysqli_query( $legacydb->dbh,'SELECT ez_emulator.handle,ez_votes.* FROM ez_votes LEFT JOIN ez_emulator ON (ez_votes.emulatorid = ez_emulator.id) ORDER BY ez_votes.id ASC' );
if ( $legacydb->dbh->errno )
	echo $legacydb->dbh->error;
if ( $result ) {
	$i = 0;
	while ( ( $row = mysqli_fetch_array( $result, MYSQLI_ASSOC ) ) ) {
		$i++;
		// If emulator is deleted, this causes 'handle' field to return NULL for "orphaned" votes
		if ( !is_null( $row['handle'] ) )
		{
			$data = array();
			$data['emulator_id'] = $row['handle'];
			$data['user_hash'] = wp_hash($row['ip_a'].'.'.$row['ip_b'].'.'.$row['ip_d'].'.'.$row['ip_d']);
			$data['rating'] = $row['rating'];
			$data['vote_date'] = sprintf( '%02d', $row['year'] ). '-' .sprintf( '%02d', $row['month'] ) . '-' . sprintf( '%02d', $row['day']) . ' 00:00:00';
			$wpdb->replace( $wpdb->prefix . 'ezvotes', $data );
			if ( $wpdb->dbh->errno )
				die( $wpdb->dbh->error );
			unset($data);
			// Print a dot for every 1000 rows converted to indicate activity
			if ( $i % 1000 == 0 )
				echo '.';
		}
	}
	mysqli_free_result( $result );
}
echo "\n" . $i . ' rows converted' . "\n";
