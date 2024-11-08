<?php

/*
Plugin Name: The Emulator Zone
Plugin URI: https://github.com/ghdpro/emuzone-plugin
Description: WordPress plugin for The Emulator Zone
Version: 0.1.0
Author: ghdpro
Author URI: https://www.emulator-zone.com/
License: AGPL v3.0
*/

const EMUZONE_CACHE_TTL = 3600;
$legacydb = null;

/**
 * Upon activation, create ezvotes table for keeping track for emulator votes
 *
 * @return void
 */
function emuzone_plugin_install(): void {
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$emuzone_plugin_db_version = '1.0';
	add_option( 'emuzone_plugin_db_version', $emuzone_plugin_db_version );
	$charset_collate = $wpdb->get_charset_collate();
	// "ezvotes" table
	$table_name = $wpdb->prefix . 'ezvotes';
	$sql = "CREATE TABLE {$table_name} (
  	emulator_id varchar(50) NOT NULL,
  	user_hash varchar(50) NOT NULL,
  	rating smallint(3) NOT NULL,
  	vote_date timestamp NOT NULL DEFAULT current_timestamp(),
  	PRIMARY KEY  (emulator_id, user_hash)
	) {$charset_collate};";
	dbDelta( $sql );
	unset( $sql );
	// "ezfiles" table
	$table_name = $wpdb->prefix . 'ezfiles';
	$sql = "CREATE TABLE {$table_name} (
  	id bigint(20) NOT NULL AUTO_INCREMENT,
  	emulator_id varchar(50) NOT NULL,
  	active_file bigint(20) NULL,
  	user_id bigint(20) NOT NULL,
  	updated timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY  (id),
    UNIQUE KEY emulator_id (emulator_id)
	) {$charset_collate};";
	dbDelta( $sql );
	unset( $sql );
	// "ezdownloads" table
	$table_name = $wpdb->prefix . 'ezdownloads';
	$sql = "CREATE TABLE {$table_name} (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	emulator_id bigint(20) DEFAULT NULL,
	checksum_sha256 varchar(100) NOT NULL,
	size bigint(20) NOT NULL,
	filename varchar(250) NOT NULL,
	name varchar(100) DEFAULT NULL,
	version varchar(100) DEFAULT NULL,
	description varchar(250) DEFAULT NULL,
	platform smallint(6) DEFAULT NULL,
	license smallint(6) DEFAULT NULL,
	release_date date DEFAULT NULL,
  	homepage1_url varchar(250) DEFAULT NULL,
  	homepage1_safe tinyint(1) DEFAULT NULL,
  	homepage1_checked timestamp NULL DEFAULT NULL,
  	homepage2_url varchar(250) DEFAULT NULL,
  	homepage2_safe tinyint(1) DEFAULT NULL,
  	homepage2_checked timestamp NULL DEFAULT NULL,
  	origin_url varchar(250) DEFAULT NULL,
  	file_safe tinyint(1) DEFAULT NULL,
  	file_checked timestamp NULL DEFAULT NULL,
  	downloads bigint(20) NOT NULL DEFAULT 0,
  	source1_url varchar(250) DEFAULT NULL,
  	source2_url varchar(250) DEFAULT NULL,
  	user_id bigint(20) NOT NULL,
  	updated timestamp NOT NULL DEFAULT current_timestamp(),
  	PRIMARY KEY  (id),
  	KEY emulator_id (emulator_id),
  	UNIQUE KEY checksum_sha256 (checksum_sha256),
  	KEY source1_url (source1_url),
  	KEY source2_url (source2_url)
	) {$charset_collate};";
	dbDelta( $sql );
	unset( $sql );
	// "ezcount" table
	$table_name = $wpdb->prefix . 'ezcount';
	$sql = "CREATE TABLE {$table_name} (
	emulator_id bigint(20) NOT NULL,
	date_year smallint(4) NOT NULL,
	date_month tinyint(2) NOT NULL,
	downloads bigint(20) NOT NULL,
    PRIMARY KEY  (emulator_id, date_year, date_month)
	) {$charset_collate};";
	dbDelta( $sql );
	unset( $sql );
	if ( ! defined( 'EMUZONE_DOWNLOAD_URL' ) || ! defined( 'EMUZONE_DOWNLOAD_PATH' ) ) {
		set_transient( 'emuzone_admin_notice', '<b>Error</b>: Please set <code>EMUZONE_DOWNLOAD_URL</code> and/or <code>EMUZONE_DOWNLOAD_PATH</code> constants in <code>wp-config.php</code> file.', DAY_IN_SECONDS );
	}
}
register_activation_hook( __FILE__, 'emuzone_plugin_install' );

function emuzone_admin_notice(): void {
	$transient = 'emuzone_admin_notice';
	$message = get_transient( $transient );
	if ( $message !== false ) {
		wp_admin_notice( $message, array(
			'type' => 'error',
			'dismissible' => true,
			'additional_classes' => array( 'notice-alt' ),
		));
		delete_transient( $transient );
	}
}
add_action( 'admin_notices', 'emuzone_admin_notice' );

require_once( plugin_dir_path( __FILE__ ) . '/legacy-config.php' );

/**
 * Utility function for connecting to the legacy database (used for downloads and converting old vote data)
 *
 * @return void
 */
function emuzone_legacydb_connect(): void {
	global $legacydb;
	if ( is_null( $legacydb ) or !( $legacydb instanceof wpdb ) )
		$legacydb = new wpdb( LEGACY_DB_USER, LEGACY_DB_PASS, LEGACY_DB_NAME, LEGACY_DB_HOST );
	else
		$legacydb->check_connection();
}

/**
 * Add 'The Emulator Zone' block category
 *
 * @param $block_categories
 * @param $editor_context
 *
 * @return mixed
 */
function filter_block_categories_when_post_provided( $block_categories, $editor_context ) {
	if ( ! empty( $editor_context->post ) ) {
		$block_categories[] = array(
			'slug'  => 'emuzone',
			'title' => __( 'The Emulator Zone', 'emuzone-plugin' ),
			'icon'  => null,
		);
	}
	return $block_categories;
}
add_filter( 'block_categories_all', 'filter_block_categories_when_post_provided', 10, 2 );

require_once( plugin_dir_path( __FILE__ ) . 'blocks/block-section.php' );
require_once( plugin_dir_path( __FILE__ ) . 'blocks/block-voting.php' );
require_once( plugin_dir_path( __FILE__ ) . 'blocks/block-download.php' );

/**
 * Initialize/register Gutenberg blocks
 *
 * @return void
 */
function emuzone_plugin_block_init(): void {
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
	acf_register_block_type( array(
		'name' => 'emuzone-plugin/download',
		'title' => 'EZ Download',
		'description' => 'The Emulator Zone file downloads',
		'category' => 'emuzone',
		'icon' => 'download',
		'mode' => 'edit',
		'render_callback' => 'emuzone_download_callback',
		'supports' => array(
			'align' => false,
		)
	) );
}
add_action( 'acf/init', 'emuzone_plugin_block_init' );

require_once( plugin_dir_path( __FILE__ ) . '/classes/class-topdownloadswidget.php' );

/**
 * Initialize/register widgets (other widgets are in the theme)
 *
 * @return void
 */
function emuzone_plugin_widgets_init(): void {
	register_widget( 'TopDownloadsWidget' );
}
add_action( 'widgets_init', 'emuzone_plugin_widgets_init' );

/**
 * Register The Emulator Zone dashboard widget
 *
 * @return void
 */
function emuzone_register_dashboard_widget(): void {
	add_meta_box( 'emuzone_dashboard', 'The Emulator Zone', 'emuzone_dashboard_widget', 'dashboard', 'normal', 'high' );
}
add_action( 'wp_dashboard_setup', 'emuzone_register_dashboard_widget' );

/**
 * Display The Emulator Zone dashboard widget
 *
 * @return void
 */
function emuzone_dashboard_widget( $post, $callback_args ): void {
	$widget = plugin_dir_path( __FILE__ ) . 'private/dashboard.html';
	if ( file_exists( $widget ) ) {
		include( $widget );
	} else {
		echo '<p class="error"><b>Error:</b> dashboard widget file missing.</p>';
	}
}

/**
 * Utility function for obtaining real IP of user, even if behind a proxy (if proxy exposed real IP)
 *
 * @return mixed|string
 */
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

require_once( plugin_dir_path( __FILE__ ) . '/classes/class-fileman.php' );
require_once( plugin_dir_path( __FILE__ ) . '/classes/class-ezfiles.php' );
require_once( plugin_dir_path( __FILE__ ) . '/classes/class-ezdownloads.php' );

/**
 * Register File Manager custom admin page
 *
 * @return void
 */
function emuzone_register_fileman_menu(): void {
	$fileman = new Fileman( plugin_dir_path( __FILE__ ) . '/templates' );

	add_menu_page(
		$fileman->get_page_title(),
		$fileman->get_menu_title(),
		$fileman->get_capability(),
		$fileman->get_menu_slug(),
		array( $fileman, 'render' ),
		$fileman->get_menu_icon(),
		$fileman->get_menu_position(),
	);
}
add_action( 'admin_menu', 'emuzone_register_fileman_menu' );

/**
 * Register File Handles custom admin page
 *
 * @return void
 */
function emuzone_register_ezfiles_menu(): void {
	$ezfiles = new ezFiles( plugin_dir_path( __FILE__ ) . '/templates' );

	add_submenu_page(
		$ezfiles->get_parent_slug(),
		$ezfiles->get_page_title(),
		$ezfiles->get_menu_title(),
		$ezfiles->get_capability(),
		$ezfiles->get_menu_slug(),
		array( $ezfiles, 'controller' ),
	);
}
add_action( 'admin_menu', 'emuzone_register_ezfiles_menu' );

function emuzone_register_ezfiles_post(): void {
	$ezfiles = new ezFiles( plugin_dir_path( __FILE__ ) . '/templates' );
	$ezfiles->controller();
}
add_action( 'admin_post_ezfiles', 'emuzone_register_ezfiles_post' );

/**
 * Register Downloads custom admin page
 *
 * @return void
 */
function emuzone_register_ezdownloads_menu(): void {
	$ezdownloads = new ezDownloads( plugin_dir_path( __FILE__ ) . '/templates' );

	add_submenu_page(
		$ezdownloads->get_parent_slug(),
		$ezdownloads->get_page_title(),
		$ezdownloads->get_menu_title(),
		$ezdownloads->get_capability(),
		$ezdownloads->get_menu_slug(),
		array( $ezdownloads, 'controller' ),
	);
}
add_action( 'admin_menu', 'emuzone_register_ezdownloads_menu' );

function emuzone_register_ezdownloads_post(): void {
	$ezdownloads = new ezDownloads( plugin_dir_path( __FILE__ ) . '/templates' );
	$ezdownloads->controller();
}
add_action( 'admin_post_ezdownloads', 'emuzone_register_ezdownloads_post' );

function emuzone_plugin_admin_scripts(): void {
	wp_enqueue_style( 'emuzone-plugin', plugin_dir_url( __FILE__ ) . 'assets/emuzone-plugin.css', array(), '1.0' );
	wp_enqueue_script( 'htmx', plugin_dir_url( __FILE__ ) . 'assets/htmx.min.js', array(), '2.0.2' );
}
add_action( 'admin_enqueue_scripts', 'emuzone_plugin_admin_scripts' );

/**
 * Following three hooks setup /download/ custom URL
 */
add_action( 'init',  function() {
	add_rewrite_rule( 'download/([a-z0-9-]+)[/]?$', 'index.php?ezdownload=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function( $query_vars ) {
	$query_vars[] = 'ezdownload';
	return $query_vars;
} );

add_filter( 'template_include', function( $template ) {
	if ( get_query_var( 'ezdownload' ) == false || get_query_var( 'ezdownload' ) == '' ) {
		return $template;
	}
	global $wpdb;
	// Fetch download
	$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE checksum_sha256 = %s", get_query_var( 'ezdownload' ) ) );
	if ( $wpdb->num_rows == 0 ) {
		wp_die('Download <b>' . esc_html( get_query_var( 'ezdownload' ) ) . '</b> not found.' );
	}
	$url = EMUZONE_DOWNLOAD_URL . urlencode( $item->checksum_sha256 ) . '/' . urlencode( $item->filename );
	wp_redirect( $url, 302 );
	// Count download (ezdownload table)
	$result = $wpdb->update( $wpdb->prefix . 'ezdownloads',
		array(
			'downloads' => ( intval ( $item->downloads ) + 1 ),
		),
		array(
			'id' => $item->id,
		)
	);
	// Count download (ezcount table)
	$sql = "INSERT INTO {$wpdb->prefix}ezcount (emulator_id,date_year,date_month,downloads) VALUES (%d,%d,%d,%d) ON DUPLICATE KEY UPDATE downloads = downloads + 1";
	$sql = $wpdb->prepare( $sql, $item->emulator_id, date('Y'), date('m'), 1 );
	$wpdb->query( $sql );
	exit;
} );
