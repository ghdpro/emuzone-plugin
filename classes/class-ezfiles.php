<?php

require_once( plugin_dir_path( __FILE__ ) . '/class-customadminpage.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * File Handles (ezfiles table) custom admin page class
 */
class ezFiles extends CustomAdminPage {

	public function get_menu_title(): string {
		return 'Handles';
	}

	public function get_page_title(): string {
		return $this->get_menu_title();
	}

	public function get_parent_slug(): string {
		return 'fileman';
	}

	public function get_menu_slug(): string {
		return 'ezfiles';
	}
}

class ezFiles_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => 'Handle',
			'plural'   => 'Handles',
			'ajax'     => false
		] );
	}

	function get_columns() {
		$columns = array(
			'emulator_id' => 'Handle',
			'user_id'     => 'Added by',
			'updated'     => 'Last Change'
		);

		return $columns;
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array(),
			'emulator_id'
		);

		$per_page     = $this->get_items_per_page( 'items_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );

		$this->items = self::get_items( $per_page, $current_page );
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

	public static function get_items( $per_page = 50, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}ezfiles";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		$sql    .= " LIMIT $per_page";
		$sql    .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ezfiles";

		return $wpdb->get_var( $sql );
	}
}
