<?php

function emuzone_plugin_voting_block_callback( $attributes, $content ) {
	return '<div>'.print_r($attributes, true) . $content . date( 'U' ).'</div>';
}
