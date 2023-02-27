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
	 * Renders widget output to string
	 *
	 * @return string
	 */
	protected function render(): string {
		global $legacydb;
		emuzone_legacydb_connect();
		$output = '';
		$result = $legacydb->get_results( 'SELECT name, version, handle FROM ez_files ORDER BY downloads DESC LIMIT 0,10' );
		$output .= '<ul>' . "\n";
		foreach ( $result as $row ) {
			$link = null;
			$query = new WP_Query( array( 's' => $row->handle ) );
			if ( $query->have_posts() ) {
				// Found a page with handle
				$query->the_post();
				$link = get_the_permalink();
			}
			$name = $row->name;
			if ( !empty( $row->version ) ) {
				$name .= ' ' . $row->version;
			}
			if ( !empty( $link ) ) {
				$output .= '<li><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></li>' . "\n";
			} else {
				$output .= '<li><span class="text-danger">' . esc_html( $name ) . '</span></li>' . "\n";
			}
		}
		$output .= '</ul>' . "\n";
		wp_reset_postdata();
		return $output;
	}

	/**
	 * Displays widget output
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		echo '<h2 class="widget-title">Top Downloads</h2>';
		echo $this->render();
	}
}
