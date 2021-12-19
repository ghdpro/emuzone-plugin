<?php

/*
Plugin Name: The Emulator Zone
Plugin URI: https://github.com/ghdpro/emuzone-plugin
Description: WordPress plugin for The Emulator Zone
Version: 0.0.1
Author: ghdpro
Author URI: https://www.emulator-zone.com/
License: AGPL v3.0
*/

const EMUZONE_CACHE_TTL = 3600;
$legacydb = null;

function emuzone_plugin_install() {
	global $wpdb;
	$emuzone_plugin_db_version = '1.0';
	$table_name = $wpdb->prefix . 'ezvotes';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
  	emulator_id varchar(50) NOT NULL,
  	user_hash varchar(50) NOT NULL,
  	rating smallint(3) NOT NULL,
  	vote_date timestamp NOT NULL DEFAULT current_timestamp(),
  	PRIMARY KEY  (emulator_id, user_hash)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	add_option( 'emuzone_plugin_db_version', $emuzone_plugin_db_version );
}
register_activation_hook( __FILE__, 'emuzone_plugin_install' );

require_once( plugin_dir_path( __FILE__ ) . '/legacy-config.php' );

function emuzone_legacydb_connect() {
	global $legacydb;
	$legacydb = new wpdb( LEGACY_DB_USER, LEGACY_DB_PASS, LEGACY_DB_NAME, LEGACY_DB_HOST );
}

function filter_block_categories_when_post_provided( $block_categories, $editor_context ) {
	if ( ! empty( $editor_context->post ) ) {
		array_push(
			$block_categories,
			array(
				'slug'  => 'emuzone',
				'title' => __( 'The Emulator Zone', 'emuzone-plugin' ),
				'icon'  => null,
			)
		);
	}
	return $block_categories;
}
add_filter( 'block_categories_all', 'filter_block_categories_when_post_provided', 10, 2 );

require_once( plugin_dir_path( __FILE__ ) . 'blocks/block-section.php' );
require_once( plugin_dir_path( __FILE__ ) . 'blocks/block-voting.php' );

function emuzone_plugin_block_init() {
	acf_register_block_type( array(
		'name' => 'emuzone-plugin/section',
		'title' => 'EZ Section',
		'description' => 'The Emulator Zone section',
		'category' => 'emuzone',
		'icon' => 'index-card',
		'mode' => 'edit',
		'render_callback' => 'emuzone_section_callback',
		'supports' => array(
			'align' => false,
		)
	) );
	acf_register_block_type( array(
		'name' => 'emuzone-plugin/voting',
		'title' => 'EZ Voting',
		'description' => 'The Emulator Zone emulator voting feature',
		'category' => 'emuzone',
		'icon' => 'star-filled',
		'mode' => 'edit',
		'render_callback' => 'emuzone_voting_callback',
		'supports' => array(
			'align' => false,
		)
	) );
}
add_action( 'acf/init', 'emuzone_plugin_block_init' );

require_once( plugin_dir_path( __FILE__ ) . '/classes/class-topdownloadswidget.php' );

function emuzone_plugin_widgets_init() {
	register_widget( 'TopDownloadsWidget' );
}
add_action( 'widgets_init', 'emuzone_plugin_widgets_init' );

function emuzone_get_ip() {
	$result = '';
	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$result = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && preg_match_all( '#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches ) ) {
		foreach ( $matches[0] as $ip ) {
			// Ignore internal IPs
			if ( !preg_match( "#^(10|172\.16|192\.168)\.#", $ip ) ) {
				$result = $ip;
			}
		}
	} elseif ( isset( $_SERVER['HTTP_FROM'] ) && ! empty( $_SERVER['HTTP_FROM'] ) ) {
		$result = $_SERVER['HTTP_FROM'];
	}
	// No proxy detected
	if ( empty( $result ) )
		$result = $_SERVER["REMOTE_ADDR"];
	return $result;
}
