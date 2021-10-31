<?php

function emuzone_plugin_block_section_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$output = '';
	// Get "Section"
	$emulators = get_field('emulator');
	if (is_array($emulators))
	{
		foreach ($emulators as $emulator)
		{
			// Get "field" on related page
			$field = get_field('emulator', $emulator->ID);
			$output .=  '<div> {' . $field . '} </div>';
		}
	}
	echo $output;
}
