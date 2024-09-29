<?php

require_once( plugin_dir_path( __FILE__ ) . '/class-customadminpage.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class ezDownloads extends CustomAdminPage {

	public function get_menu_title(): string {
		return 'Downloads';
	}

	public function get_page_title(): string {
		return $this->get_menu_title();
	}

	public function get_parent_slug(): string {
		return 'fileman';
	}

	public function get_menu_slug(): string {
		return 'ezdownloads';
	}

	/**
	 * Controls what needs to be displayed or processed
	 *
	 * @return void
	 */
	public function controller(): void {
		$this->render();
	}
}

class ezDownloads_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array (
			'singular' => 'Download',
			'plural'   => 'Downloads',
			'ajax'     => false
		) );
	}

	function get_columns() {
		$columns = array(
			'emulator_id' => 'Handle',
			'name'        => 'File',
			'user_id'     => 'Added by',
			'updated'     => 'Last Modified'
		);

		return $columns;
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
			'emulator_id'
		);

		$per_page     = $this->get_items_per_page( 'items_per_page', 50 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );

		$data = self::get_items( $per_page, $current_page );
		usort( $data, array( &$this, 'usort_reorder' ) );
		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user_id':
				$user = get_user_by( 'id', $item[ $column_name ] );
				if ( false !== $user )
					return $user->display_name;
				else
					return 'Unknown';
			default:
				return $item[ $column_name ];
		}
	}

	function column_name( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id'] ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id'] ),
		);

		return sprintf( '%1$s %2$s', $item['emulator_id'], $this->row_actions( $actions ) );
	}

	public static function get_items( $per_page = 50, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}ezdownloads";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		} else {
			// Default sort order
			$sql .= ' ORDER BY updated DESC';
		}
		$sql    .= ' LIMIT ' . $per_page;
		$sql    .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ezdownloads";

		return $wpdb->get_var( $sql );
	}

	protected function get_sortable_columns(): array {
		// Only initial sort column has sort direction indicated
		return array(
			'emulator_id' => array('emulator_id', false, 'Handle', 'Ordered by Handle'),
			'name' => array('emulator_id', false, 'File', 'Ordered by File'),
			'user_id'     => array('user_id', false, 'User', 'Ordered by User'),
			'updated'     => array('updated', true, 'Date', 'Ordered by Last Modified', 'desc'),
		);
	}

	protected function usort_reorder( $a, $b ) {
		// If no order specified, default to "updated"
		$orderby = $_REQUEST['orderby'] ?? 'updated';

		// If no direction specified, default to "desc"
		$order = $_REQUEST['order'] ?? 'desc';

		// Determine sort order
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}
}
