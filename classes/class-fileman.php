<?php

require_once( plugin_dir_path( __FILE__ ) . '/class-customadminpage.php' );

/**
 * File Manager custom admin page class
 */
class FileMan extends CustomAdminPage {

	public function get_menu_title(): string {
		return 'File Manager';
	}

	public function get_page_title(): string {
		return $this->get_menu_title();
	}

	public function get_menu_slug(): string {
		return 'fileman';
	}

	public function get_menu_position(): int {
		return 27;
	}

	public function get_menu_icon(): string {
		return 'dashicons-download';
	}
}
