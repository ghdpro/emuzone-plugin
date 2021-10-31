<?php

/**
 * Top Downloads widget
 *
 * Shows list of top downloads and links back to pages where those files are listed
 */

class TopDownloadsWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'topdownloads',
			'Top Downloads',
			array( 'description' => __( 'Top Downloads widget', 'emuzone' ), )
		);
	}

	/**
	 * Displays wiget output
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		echo '<h2 class="widget-title">Top Downloads</h2>';
		echo '<p>To Do</p>';
	}
}
