<?php

/*
Plugin Name: The Emulator Zone
Plugin URI: https://github.com/ghdpro/emuzone-plugin
Description: WordPress plugin for The Emulator Zone site features
Version: 0.1
Author: ghdpro
Author URI: https://www.emulator-zone.com/
License: AGPL v3.0
*/


function emuzone_plugin_voting_block_callback( $attributes, $content ) {
	return '<div>'.print_r($attributes, true) . $content . date( 'U' ).'</div>';
}

function emuzone_plugin_section_block_callback( $attributes, $content ) {
	$output = '';
	// Get "Section"
	$emulators = get_field('emulator');
	foreach ($emulators as $emulator)
	{
		// Get "field" on related page
		$field = get_field('emulator', $emulator->ID);
		$output .=  '<div>' . $field . '</div>';
	}
	return $output;
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


function emuzone_plugin_block_init() {
	register_block_type( plugin_dir_path( __FILE__ ) . 'blocks/voting/', array(
		'render_callback' => 'emuzone_plugin_voting_block_callback'
	));
	register_block_type( plugin_dir_path( __FILE__ ) . 'blocks/section/', array(
		'render_callback' => 'emuzone_plugin_section_block_callback'
	));
}
add_action( 'init', 'emuzone_plugin_block_init' );
