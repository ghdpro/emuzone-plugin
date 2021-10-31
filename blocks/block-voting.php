<?php

function emuzone_plugin_voting_block_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	echo '<div>'.get_field('voting_id') . $content . date( 'U' ).'</div>';
}
