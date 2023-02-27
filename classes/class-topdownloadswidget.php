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
		global $wpdb, $legacydb;
		emuzone_legacydb_connect();
		$output = '';
		$result = $legacydb->get_results( 'SELECT name, version, handle FROM ez_files ORDER BY downloads DESC LIMIT 0,10' );
		$output .= '<ul>' . "\n";
		foreach ( $result as $row ) {
			$link = null;
			$query = $wpdb->get_row( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . " WHERE post_type = 'page' AND post_content LIKE %s", '%'. $wpdb->esc_like('file_id":"' . $row->handle . '"') . '%') );
			if ( !is_null( $query ) ) {
				// Found a page with handle
				$link = get_permalink( $query->ID );
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
		$result = wp_cache_get( 'ez_top_downloads' );
		if ( $result === false ) {
			$result = $this->render();
			wp_cache_set( 'ez_top_downloads', $result, '', 300 );
		}
		echo $result;
	}
}
