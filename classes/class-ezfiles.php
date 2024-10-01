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
		global $wpdb;
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
				case 'edit':
					$this->process_edit();
					break;
				case 'delete':
					$this->process_delete();
					break;
				default:
					wp_die( 'Unknown action.' );
			}
		} else {
			$item = null;
			$id = $_REQUEST['id'] ?? 0;
			if ( $id ) {
				$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE id = %d", $id ) );
			}
			switch ( $action ) {
				case 'add':
					$this->render_custom( 'form', array( 'action' => 'add', 'action_display' =>'Add', 'item' => $item ) );
					break;
				case 'edit':
					$this->render_custom( 'form', array( 'action' => 'edit', 'action_display' =>'Edit', 'item' => $item ) );
					break;
				case 'delete':
					$this->render_custom( 'delete', array( 'action' => 'delete', 'action_display' =>'Delete', 'item' => $item ) );
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
		// Check minimum and maximum length
		if ( ( strlen( $handle ) < 3 ) || ( strlen( $handle ) > 40 ) ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $handle ) . '</b> is either too short or too long. Length must be between 3 and 40 characters.' );
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

	/**
	 * Processes form data for editing handle
	 */
	protected function process_edit(): void {
		global $wpdb;
		$id = intval( $_REQUEST['id']  ?? 0 );
		$handle = strtolower( trim( $_REQUEST['emulator_id'] ?? '' ) );
		// Check if valid handle
		if ( ! preg_match( '/^[a-z0-9-_]+$/', $handle ) ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $handle ) . '</b> is invalid: only a-z, 0-9, hyphen or underscore are allowed.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() . '&action=edit&id=' . $id ) );
			return;
		}
		// Check minimum and maximum length
		if ( ( strlen( $handle ) < 3 ) || ( strlen( $handle ) > 40 ) ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $handle ) . '</b> is either too short or too long. Length must be between 3 and 40 characters.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() . '&action=add' ) );
			return;
		}
		// Check if it doesn't already exist
		$item = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE emulator_id = %s", $handle ) );
		if ( ( $wpdb->num_rows > 0 ) && $item && ( $item->id != $id ) ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $handle ) . '</b> already exists.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() . '&action=edit&id=' . $id ) );
			return;
		}
		// Update
		$result = $wpdb->update( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'emulator_id' => $handle,
				'user_id'     => get_current_user_id(),
			),
			array(
				'id' => $id,
			)
		);
		if ( $result !== false ) {
			$this->set_message( 'success', 'Handle <b>' . esc_html( $handle ) . '</b> modified.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() ) );
			return;
		} else {
			wp_die( 'Query failed.' );
		}
	}

	protected function process_delete(): void {
		global $wpdb;
		$id = intval( $_REQUEST['id']  ?? 0 );
		// Check for associated downloads
		$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE id = %d", $id ) );
		if ( $item->active_file > 0 ) {
			$this->set_message( 'error', 'Handle <b>' . esc_html( $item->handle ) . '</b> has associated downloads and cannot be deleted.' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->get_menu_slug() ) );
			return;
		}
		// Delete
		$result = $wpdb->delete( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'id' => $id,
			)
		);
		if ( $result !== false ) {
			$this->set_message( 'success', 'Handle <b>' . esc_html( $item->handle ) . '</b> deleted.' );
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

	function column_emulator_id( $item ) {
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id'] ),
		);
		// Don't show delete link if not eligible for deletion anyway
		if ( $item['active_file'] == 0 ) {
			$actions['delete'] = sprintf( '<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['id'] );
		}
		return sprintf( '%1$s %2$s', $item['emulator_id'], $this->row_actions( $actions ) );
	}

	public static function get_items( $per_page = 50, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}ezfiles";
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

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ezfiles";

		return $wpdb->get_var( $sql );
	}

	protected function get_sortable_columns(): array {
		// Only initial sort column has sort direction indicated
		return array(
			'emulator_id' => array('emulator_id', false, 'Handle', 'Ordered by Handle'),
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
