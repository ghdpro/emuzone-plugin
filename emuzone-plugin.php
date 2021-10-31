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
		'render_callback' => 'emuzone_plugin_block_section_callback',
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
		'render_callback' => 'emuzone_plugin_block_voting_callback',
		'supports' => array(
			'align' => false,
		)
	) );
}
add_action( 'acf/init', 'emuzone_plugin_block_init' );

if ( ! function_exists( 'emuzone_plugin_scripts' ) ) :
	function emuzone_plugin_scripts() {
		$plugin = get_plugin_data( __FILE__ );
		wp_enqueue_style(
			'emuzone-plugin',
			plugins_url( 'css/emuzone-plugin.css',  __FILE__ ),
			array(),
			$plugin[ 'Version' ]
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'emuzone_plugin_scripts' );

require_once( plugin_dir_path( __FILE__ ) . '/classes/class-topdownloadswidget.php' );

if ( ! function_exists( 'emuzone_plugin_widgets_init' ) ) :
	function emuzone_plugin_widgets_init() {
		register_widget( 'TopDownloadsWidget' );
	}
endif;
add_action( 'widgets_init', 'emuzone_plugin_widgets_init' );

