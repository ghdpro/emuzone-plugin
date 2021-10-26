<?php

function emuzone_plugin_section_block_callback( $attributes, $content ) {
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
	return $output;
}
