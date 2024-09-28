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

	/**
	 * Controls what needs to be displayed or processed
	 *
	 * @return void
	 */
	public function controller(): void {
		if ( ! empty ( $_REQUEST['form_action'] ) ) {
			$action = strtolower( trim( $_REQUEST['form_action'] ) );
		} else {
			$action = strtolower( trim( $_REQUEST['action'] ?? '' ) );
		}
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			// Verify nonce
			check_admin_referer( $this->get_menu_slug() . $action);
			// Verify capability
			if ( ! current_user_can( $this->get_capability() ) ) {
				wp_die( 'Not authorized.' );
			}
			switch ( $action ) {
				case 'add':
					$this->process_add();

					break;
				default:
					wp_die( 'Unknown action.' );
			}

		} else {
			switch ( $action ) {
				case 'add':
					$this->render_form( array( 'action' => 'add', 'action_display' =>'Add' ) );
					break;
				case 'edit':
					$this->render_form( array( 'action' => 'edit', 'action_display' =>'Edit' ) );
					break;
				default:
					$this->render();
			}
		}
	}

	/**
	 * Processes form data for adding handle
	 *
	 * @return void
	 */
	protected function process_add(): void {
		global $wpdb;
		$handle = strtolower( trim( $_REQUEST['emulator_id'] ?? '' ) );
		// Check if valid handle
		if ( ! preg_match( '/^[a-z0-9-_]+$/', $handle ) ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $handle ) . '</b> is invalid: only a-z, 0-9, hyphen or underscore are allowed.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() . '&action=add' ) );
			return;
		}
		// Check if it doesn't already exist
		$wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE emulator_id = %s", $handle ) );
		if ( $wpdb->num_rows > 0 ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $handle ) . '</b> already exists.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() . '&action=add' ) );
			return;
		}
		// Insert
		$result = $wpdb->insert( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'emulator_id' => $handle,
				'user_id'     => get_current_user_id(),
			) );
		if ( $result !== false ) {
			$this->set_message( 'success', 'Handle <b>' . esc_html( $handle ) . '</b> added.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() ) );
			return;
		} else {
			wp_die( 'Query failed.' );
		}
	}
}

class ezFiles_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array (
			'singular' => 'Handle',
			'plural'   => 'Handles',
			'ajax'     => false
		) );
	}

	function get_columns() {
		$columns = array(
			'emulator_id' => 'Handle',
			'user_id'     => 'Added by',
			'updated'     => 'Last Modified'
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
