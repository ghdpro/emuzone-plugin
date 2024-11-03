<?php

require_once( plugin_dir_path( __FILE__ ) . '/class-customadminpage.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( plugin_dir_path( __DIR__ ) . '/blocks/block-download.php' );

if ( ! function_exists( 'html_selection_box' ) ) :
	function html_selection_box( string $name = '', array $options = array(), $selected = null ): string {
		$result = '<select name="' . $name . '" id="' . $name . '">';
		foreach ( $options as $key => $value ) {
			$result .= '<option value="' . $key . '"';
			if ( $key == $selected ) {
				$result .= ' selected="selected"';
			}
			$result .= '>' . $value . '</option>';
		}
		$result .= '</select>';
		return $result;
	}
endif;

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
		global $wpdb;
		if ( ! empty ( $_REQUEST['form_action'] ) ) {
			$action = strtolower( trim( $_REQUEST['form_action'] ) );
		} else {
			$action = strtolower( trim( $_REQUEST['action'] ?? '' ) );
		}
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			// Verify nonce
			check_admin_referer( $this->get_menu_slug() . $action );
			// Verify capability
			if ( ! current_user_can( $this->get_capability() ) ) {
				wp_die( 'Not authorized.' );
			}
			switch ( $action ) {
				case 'transfer':
					$this->process_transfer();
					break;
				case 'upload':
					$this->process_upload();
					break;
				case 'link':
					$this->process_link();
					break;
				case 'search';
					$this->process_search();
					break;
				case 'edit':
					$this->process_edit();
					break;
				case 'active':
					$this->process_active();
					break;
				case 'delete':
					$this->process_delete();
					break;
				default:
					wp_die( 'Unknown action.' );
			}
		} else {
			$item = null;
			$id   = $_REQUEST['id'] ?? 0;
			if ( $id ) {
				$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE id = %d", $id ) );
			}
			switch ( $action ) {
				case 'link':
					$this->render_custom( 'link', array( 'item' => $item ) );
					break;
				case 'edit':
					$this->render_custom( 'form', array( 'action' => 'edit', 'action_display' =>'Edit', 'item' => $item ) );
					break;
				default:
					$this->render();
			}
		}
	}

	/**
	 * Process transfer a download from another site
	 *
	 * @return void
	 */
	protected function process_transfer(): void {
		global $wpdb;
		$url = strtolower( trim( $_REQUEST['url'] ?? '' ) );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Emulator-Zone v1.0; https://wwww.emulator-zone.com/)' );
		$data = curl_exec( $ch );
		if ( $data === false || ( curl_errno( $ch ) !== 0 ) ) {
			$this->set_message( 'error', curl_error( $ch ) );
			wp_safe_redirect( admin_url( 'admin.php?page=fileman' ) );
			exit;
		}
		$httpcode = intval( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) );
		if ( $httpcode >= 400 ) {
			$this->set_message( 'error', 'Server returned HTTP <b>' . $httpcode . '</b> error.' );
			wp_safe_redirect( admin_url( 'admin.php?page=fileman' ) );
			exit;
		}
		curl_close( $ch );
		unset( $ch );
		$filename = basename( parse_url( $url, PHP_URL_PATH ) );
		$sha256 = hash( 'sha256', $data );
		// Check if download already exists
		$wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE checksum_sha256 = %s", $sha256 ) );
		if ( $wpdb->num_rows > 0 ) {
			$this->set_message( 'error', 'File <b>' . esc_html( $filename ) . '</b> already exists.' );
			wp_safe_redirect( admin_url( 'admin.php?page=fileman' ) );
			exit;
		}
		$size = strlen( $data );
		// Store file
		$output_file = EMUZONE_DOWNLOAD_PATH . $sha256;
		$result = file_put_contents( $output_file, $data );
		if ( $result === false ) {
			$this->set_message( 'error', 'Failed to write file <b>' . esc_html( $output_file ) . '</b>.' );
			wp_safe_redirect( admin_url( 'admin.php?page=fileman' ) );
			exit;
		}
		// Insert
		$result = $wpdb->insert( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'checksum_sha256' => $sha256,
				'size' => $size,
				'filename' => $filename,
				'origin_url' => $url,
				'user_id' => get_current_user_id(),
				'updated' => date( 'Y-m-d H:i:s' ),
			) );
		if ( $result !== false ) {
			// This message may be overwritten bij auto_link()
			$this->set_message( 'success', 'File <b>' . esc_html( $filename ) . '</b> (' . filesize_human( $size )[0] . ' ' . filesize_human( $size )[1] . ') successfully transferred.' );
			$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE checksum_sha256 = %s", $sha256 ) );
			// Attempt auto linking
			$this->auto_link( $item );
			// If we reach this point, auto link failed
			wp_safe_redirect( admin_url( 'admin.php?page=ezdownloads&action=link&id=' . esc_html( $item->id ) ) );
			exit;
		} else {
			wp_die( 'Query failed.' );
		}
	}

	protected function process_upload(): void {
		global $wpdb;
		if ( ! isset( $_FILES[ 'file' ] ) || $_FILES[ 'file' ][ 'error' ] != UPLOAD_ERR_OK ) {
			$this->set_message( 'error', 'Upload failed.' );
			$redirect = admin_url( 'admin.php?page=fileman' );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Upload failed. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		}
		// Store file
		$sha256 = hash_file( 'sha256', $_FILES[ 'file' ][ 'tmp_name' ] );
		// Check if download already exists
		$wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE checksum_sha256 = %s", $sha256 ) );
		if ( $wpdb->num_rows > 0 ) {
			$this->set_message( 'error', 'File <b>' . esc_html( $_FILES[ 'file' ][ 'name' ] ) . '</b> already exists.' );
			$redirect = admin_url( 'admin.php?page=fileman' );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Upload failed. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		}

		$dest_file = EMUZONE_DOWNLOAD_PATH . $sha256;
		$result = move_uploaded_file( $_FILES[ 'file' ][ 'tmp_name' ], $dest_file );
		if ( $result === false ) {
			$this->set_message( 'error', 'Failed to move uploaded file <b>' . esc_html( $_FILES[ 'file' ][ 'name' ] ) . '</b>.' );
			$redirect = admin_url( 'admin.php?page=fileman' );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Upload failed. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		}
		// Insert
		$result = $wpdb->insert( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'checksum_sha256' => $sha256,
				'size' => $_FILES[ 'file' ][ 'size' ],
				'filename' => $_FILES[ 'file' ][ 'name' ],
				'user_id' => get_current_user_id(),
				'updated' => date( 'Y-m-d H:i:s' ),
			) );
		if ( $result !== false ) {
			$this->set_message( 'success', 'File <b>' . esc_html( $_FILES[ 'file' ][ 'name' ] ) . '</b> (' . filesize_human( $_FILES[ 'file' ][ 'size' ] )[0] . ' ' . filesize_human( $_FILES[ 'file' ][ 'size' ] )[1] . ') successfully uploaded.' );
			$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE checksum_sha256 = %s", $sha256 ) );
			// htmx outputs whatever is returned, so do not use regular redirect
			$redirect = admin_url( 'admin.php?page=ezdownloads&action=link&id=' . esc_html( $item->id ) );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Upload successful. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		} else {
			wp_die( 'Query failed.');
		}
	}

	/**
	 * Processes search query in link action
	 *
	 * @return void
	 */
	protected function process_search(): void {
		global $wpdb;
		$result = '';
		$q = strtolower( trim( $_REQUEST[ 'q' ] ?? '' ) );
		$id = intval( $_REQUEST[ 'id' ] ?? 0 );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezfiles WHERE emulator_id LIKE %s", '%' . $wpdb->esc_like( $q ) . '%' ) );
		foreach ( $rows as $row ) {
			$result .= '<tr>';
			$result .= '<td>' . esc_html( $row->emulator_id ) . '</td>';
			if ( $row->active_file > 0 ) {
				$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE id = %d", $row->active_file ) );
				if ( $wpdb->num_rows > 0 ) {
					$result .= '<td>' . esc_html( $item->filename ) . '</td>';
				} else {
					$result .= '<td><i>Invalid</i></td>';
				}
			} else {
				$result .= '<td><i>None</i></td>';
			}
			$result .= '<td>';
			$result .= '<form action="' . esc_html( admin_url( 'admin-post.php' ) ) . '" method="post">';
			$result .= '<input type="hidden" name="action" value="ezdownloads">';
			$result .= '<input type="hidden" name="form_action" value="link">';
			$result .= wp_nonce_field( 'ezdownloadslink', '_wpnonce', true, false );
			$result .= '<input type="hidden" name="id" value="' . esc_html( $id ) . '">';
			$result .= '<input type="hidden" name="emulator_id" value="' . esc_html( $row->id ) . '">';
			$result .= '<input type="submit" class="button button-primary" value="Link">';
			$result .= '</form>';
			$result .= '</td>';
			$result .= '</tr>';
		}
		echo $result;
	}

	/**
	 * Attempt to automatically match origin URL to a source URL
	 *
	 * @param array $item
	 *
	 * @return void
	 */
	protected function auto_link( $item ): void {
		if ( empty( $item->origin_url ) ) {
			// No origin URL = file was not transferred (but uploaded), so nothing to do
			return;
		}
		global $wpdb;
		$url = $item->origin_url;
		$redirect = admin_url( 'admin.php?page=ezdownloads&action=edit&id=' . $item->id );
		while ( substr_count( $url, '/' ) > 3) {
			$pos = strrpos( $url, '/' );
			if ( $pos !== false ) {
				$url = substr( $url, 0, $pos );
			}
			$result = $wpdb->get_results( $wpdb->prepare( 'SELECT ' . $wpdb->prefix . $this->get_menu_slug() .  '.*,' . $wpdb->prefix . 'ezfiles.emulator_id AS handle FROM ' . $wpdb->prefix . $this->get_menu_slug()
			                                              . ' JOIN ' . $wpdb->prefix . 'ezfiles ON ' . $wpdb->prefix . 'ezfiles.id = ' . $wpdb->prefix . 'ezdownloads.emulator_id'
			                                              . " WHERE source1_url LIKE %s GROUP BY emulator_id", '%' . $wpdb->esc_like( $url ) . '%' ) );
			// We want exactly 1 hit, no more, no less
			if ( $wpdb->num_rows == 1 ) {
				$this->do_link( $item->id, $result[0]->emulator_id );
				$this->set_message( 'success', 'Automatically matched to <b>' . esc_html( $result[0]->filename ) . '</b>' );
				wp_safe_redirect( admin_url( 'admin.php?page=ezdownloads&action=edit&id=' . $item->id ) );
				exit;
			} elseif ( $wpdb->num_rows > 1 ) {
				$this->set_message( 'warning', 'Found more than one result when trying to automatically link download.' );
				return;
			}
			$result = $wpdb->get_results( $wpdb->prepare( 'SELECT ' . $wpdb->prefix . $this->get_menu_slug() .  '.*,' . $wpdb->prefix . 'ezfiles.emulator_id AS handle FROM ' . $wpdb->prefix . $this->get_menu_slug()
			                                              . ' JOIN ' . $wpdb->prefix . 'ezfiles ON ' . $wpdb->prefix . 'ezfiles.id = ' . $wpdb->prefix . 'ezdownloads.emulator_id'
			                                              . " WHERE source2_url LIKE %s GROUP BY emulator_id", '%' . $wpdb->esc_like( $url ) . '%' ) );
			// We want exactly 1 hit, no more, no less
			if ( $wpdb->num_rows == 1 ) {
				$this->do_link( $item->id, $result[0]->emulator_id );
				$this->set_message( 'success', 'Automatically matched to <b>' . esc_html( $result[0]->filename ) . '</b>' );
				wp_safe_redirect( admin_url( 'admin.php?page=ezdownloads&action=edit&id=' . $item->id ) );
				exit;
			} elseif ( $wpdb->num_rows > 1 ) {
				$this->set_message( 'warning', 'Found more than one result when trying to automatically link download.' );
				return;
			}
		}
	}

	/**
	 * Perform link action
	 *
	 * @param int $emulator_id
	 * @param int $id
	 *
	 * @return void
	 */
	protected function do_link( int $id, int $emulator_id ): void {
		global $wpdb;
		// Fetch handle (from ezfiles table) for active_file info
		$handle = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezfiles WHERE id = %d", $emulator_id ) );
		if ( $wpdb->num_rows == 0 ) {
			wp_die('Handle <b>' . esc_html( $emulator_id ) . '</b> missing?' );
		}
		// Copy values from previous file (where applicable)
		if ( empty( $handle->active_file ) ) {
			// No previous upload
			$result = $wpdb->update( $wpdb->prefix . "ezdownloads", array(
				'emulator_id' => $emulator_id,
				'updated' => date( 'Y-m-d H:i:s' ),
			), array( 'id' => $id ) );
			if ( $result === false ) {
				wp_die('Update download (no previous values) query failed.');
			}
		} else {
			// Previous active file
			$prev = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE id = %d", intval( $handle->active_file ) ) );
			if ( $wpdb->num_rows == 0 ) {
				wp_die('Previous active file <b>' . esc_html( intval( $handle->active_file ) ) . '</b> missing?' );
			}
			$result = $wpdb->update( $wpdb->prefix . "ezdownloads", array(
				'emulator_id' => $emulator_id,
				'name' => $prev->name,
				'description' => $prev->description,
				'platform' => $prev->platform,
				'license' => $prev->license,
				'homepage1_url' => $prev->homepage1_url,
				'homepage1_safe' => $prev->homepage1_safe,
				'homepage1_checked' => $prev->homepage1_checked,
				'homepage2_url' => $prev->homepage2_url,
				'homepage2_safe' => $prev->homepage2_safe,
				'homepage2_checked' => $prev->homepage2_checked,
				'source1_url' => $prev->source1_url,
				'source2_url' => $prev->source2_url,
				'updated' => date( 'Y-m-d H:i:s' ),
			), array( 'id' => $id ) );
			if ( $result === false ) {
				wp_die('Update download (with previous values) query failed.');
			}
		}
		// Set new active file for handle
		$result = $wpdb->update( $wpdb->prefix . "ezfiles", array(
			'active_file' => $id,
		), array( 'id' => $emulator_id ) );
		if ( $result === false ) {
			wp_die('Update active file query failed');
		}
	}

	/**
	 * Process link form, associates download with emulator handle and copies (almost) all values over
	 *
	 * @return void
	 */
	protected function process_link(): void {
		global $wpdb;
		$id = intval( $_REQUEST[ 'id' ] ?? 0 );
		$emulator_id = intval( $_REQUEST[ 'emulator_id' ] ?? 0 );
		if ( $id == 0 || $emulator_id == 0 ) {
			wp_die( 'Something went wrong.' );
		}
		$this->do_link( $id, $emulator_id );
		// Finally redirect to edit form
		$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE id = %d", $id ) );
		$handle = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezfiles WHERE id = %d", $emulator_id ) );
		$this->set_message( 'success', 'Linked <b>' . esc_html( $item->filename ) . '</b> to handle <b>' . $handle->emulator_id . '</b>.' );
		wp_safe_redirect( admin_url( 'admin.php?page=ezdownloads&action=edit&id=' . $id ) );
		exit;
	}

	protected function process_edit(): void {
		global $wpdb;
		$id = intval( $_REQUEST['id']  ?? 0 );
		$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . "ezdownloads WHERE id = %d", $id ) );
		if ( $wpdb->num_rows == 0 ) {
			wp_die( 'Download ' . $id . ' missing?' );
		}
		// Update
		$result = $wpdb->update( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'version' => ( $_REQUEST[ 'version' ] ?? null ),
				'release_date' => ( $_REQUEST[ 'release_date' ] ?? null ),
				'filename' => $_REQUEST[ 'filename' ],
				'name' => $_REQUEST[ 'name' ],
				'description' => ( $_REQUEST[ 'description' ] ?? null ),
				'platform' => ( $_REQUEST[ 'platform' ] ?? null ),
				'license' => ( $_REQUEST[ 'license' ] ?? null ),
				'homepage1_url' => ( $_REQUEST[ 'homepage1_url' ] ?? null ),
				'source1_url' => ( $_REQUEST[ 'source1_url' ] ?? null ),
				'updated' => date( 'Y-m-d H:i:s' ),
			),
			array(
				'id' => $id,
			)
		);
		if ( $result !== false ) {
			$this->set_message( 'success', 'Download <b>' . esc_html( $_REQUEST[ 'filename' ] ) . '</b> modified.' );
			wp_safe_redirect( admin_url( 'admin.php?page=fileman' ) );
			exit;
		} else {
			wp_die( 'Query failed.' );
		}
	}

	protected function process_active() {
		global $wpdb;
		$id = intval( $_REQUEST['id']  ?? 0 );
		$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE id = %d", $id ) );
		$handle = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'ezfiles' . " WHERE id = %d", $item->emulator_id ) );
		if ( $handle->active_file != $id ) {
			$wpdb->update( $wpdb->prefix . 'ezfiles', array(
				'active_file' => $item->id,
			), array( 'id' => $handle->id ) );
			$this->set_message( 'success', 'Download <b>' . esc_html( $item->filename ) . '</b> set as active download for <b>' . esc_html( $handle->emulator_id ) . '</b>.' );
			$redirect = admin_url( 'admin.php?page=fileman' );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Active download set. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		} else {
			// Already active download
			$this->set_message( 'warning', 'Download <b>' . esc_html( $item->filename ) . '</b> is already active download for <b>' . esc_html( $handle->emulator_id ) . '</b>.' );
			$redirect = admin_url( 'admin.php?page=fileman' );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Nothing to do. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		}
	}

	protected function process_delete(): void {
		global $wpdb;
		$id = intval( $_REQUEST['id']  ?? 0 );
		$item = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . $this->get_menu_slug() . " WHERE id = %d", $id ) );
		// If active_file, update it to next latest download
		$handle = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'ezfiles' . " WHERE id = %d", $item->emulator_id ) );
		if ( $wpdb->num_rows > 0 ) {
			if ( $handle->active_file == $item->id ) {
				// Download is indeed active file
				$next = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . $this->get_menu_slug()
				                                        . " WHERE emulator_id = %d AND id != %d ORDER BY release_date DESC, updated DESC LIMIT 0,1", $handle->id, $item->id ) );
				if ( $wpdb->num_rows > 0 ) {
					// Next found
					$wpdb->update( $wpdb->prefix . 'ezfiles', array(
						'active_file' => $next->id,
					), array( 'id' => $handle->id ) );
				} else {
					// Next not found (=no more downloads), so set to null
					$wpdb->update( $wpdb->prefix . 'ezfiles', array(
						'active_file' => null,
					), array( 'id' => $handle->id ) );
				}
			}
		}
		// Delete
		$result = $wpdb->delete( $wpdb->prefix . $this->get_menu_slug(),
			array(
				'id' => $id,
			)
		);
		unlink( EMUZONE_DOWNLOAD_PATH . $item->checksum_sha256 );
		if ( $result !== false ) {
			$this->set_message( 'success', 'Download <b>' . esc_html( $item->filename ) . '</b> deleted.' );
			$redirect = admin_url( 'admin.php?page=fileman' );
			echo '<meta http-equiv="refresh" content="0; url=' . $redirect . '">Deleted. <a href="' . $redirect . '">Redirecting...</a>';
			exit;
		} else {
			wp_die( 'Query failed.' );
		}	}
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
			'handle'    => 'Handle',
			'name'      => 'Name',
			'filename'  => 'File',
 			'homepage'  => 'Homepage',
			'user_id'   => 'Added by',
			'updated'   => 'Last Modified'
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
		global $wpdb;
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
		$actions = array();
		if ( empty( $item[ 'emulator_id' ] ) ) {
			$actions['link'] = sprintf( '<a href="?page=ezdownloads&action=%s&id=%s">Link</a>','link', $item['id'] );
		} else {
			$actions['edit'] = sprintf( '<a href="?page=ezdownloads&action=%s&id=%s">Edit</a>','edit', $item['id'] );
		}

		if ( ! empty( $item[ 'active_file' ] ) && ( $item[ 'active_file' ] != $item['id'] ) ) {
			$actions['active'] = sprintf( '<a hx-confirm="Do you want to set <b>%s</b> as active download for handle <b>%s</b> ?" hx-post="?page=ezdownloads&action=%s&id=%s&_wpnonce=%s" href="#">Set Active</a>',
				htmlentities( $item[ 'filename' ] ), htmlentities( $item[ 'handle' ] ), 'active', $item['id'], wp_create_nonce( 'ezdownloadsactive' ) );
		}

		$actions['delete'] = sprintf( '<a hx-confirm="Are you sure you want to delete <b>%s</b> ?" hx-post="?page=ezdownloads&action=%s&id=%s&_wpnonce=%s" href="#">Delete</a>',
			htmlentities( $item[ 'filename' ] ), 'delete', $item['id'], wp_create_nonce( 'ezdownloadsdelete' ) );

		$name = $item[ 'name' ] . ' ' . $item[ 'version' ];
		$style = '';
		if ( $item[ 'id'] != $item[ 'active_file'] ) {
			$style = ' style="color: #A0A5AA"';
		}
		$url = get_site_url() . '/download/' . $item[ 'checksum_sha256' ];
		return sprintf( '<a target="_blank" %1s href="%2$s">%3$s</a> %4$s', $style, $url, $name, $this->row_actions( $actions ) );
	}

	function column_filename( $item ) {
		return esc_html( $item[ 'filename' ] ).'<br> ('. filesize_human( $item[ 'size' ] )[0] . ' ' . filesize_human( $item[ 'size' ] )[1] .')';
	}

	function column_homepage( $item ) {
		$result = '';
		if ( !empty ( $item[ 'homepage1_url' ] ) ) {
			$result .= sprintf( ' <a target="_blank" href="%s"><span class="dashicons dashicons-admin-home"></span></a> ', esc_url( $item[ 'homepage1_url' ] ) );
		} else {
			$result .= ' <span class="dashicons dashicons-admin-home" style="color: #A0A5AA"></span> ';
		}
		if ( !empty ( $item[ 'source1_url' ] ) ) {
			$result .= sprintf( ' <a target="_blank" href="%s"><span class="dashicons dashicons-admin-site-alt3"></span></a> ', esc_url( $item[ 'source1_url' ] ) );
		} else {
			$result .= ' <span class="dashicons dashicons-admin-site-alt3" style="color: #A0A5AA"></span> ';
		}
		return $result;
	}

	public static function get_items( $per_page = 50, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT {$wpdb->prefix}ezdownloads.*,{$wpdb->prefix}ezfiles.emulator_id AS handle,{$wpdb->prefix}ezfiles.active_file FROM {$wpdb->prefix}ezdownloads";
		$sql .= " LEFT JOIN {$wpdb->prefix}ezfiles ON {$wpdb->prefix}ezfiles.id = {$wpdb->prefix}ezdownloads.emulator_id";
		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = '%' . $wpdb->esc_like( $_REQUEST['s'] ) . '%';
			$where = ' WHERE ';
			$where .= $wpdb->prefix . 'ezfiles.emulator_id LIKE "%s" OR ';
			$where .= $wpdb->prefix . 'ezdownloads.filename LIKE "%s" OR ';
			$where .= $wpdb->prefix . 'ezdownloads.name LIKE "%s" ';
			$sql .= $wpdb->prepare( $where, $search, $search, $search );
		}
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
			if ( $_REQUEST['orderby'] == 'name' ) {
				// When sorting by name, sort by version too
				$sql .= ', version ';
				$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
			}
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
			'handle'      => array('handle', false, 'Handle', 'Ordered by Handle'),
			'name'        => array('name', false, 'Name', 'Ordered by Name'),
			'filename'     => array('filename', false, 'File', 'Ordered by File'),
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
