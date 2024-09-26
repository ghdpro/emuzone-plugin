<?php

/**s
 * Custom Admin Page base class
 */
class CustomAdminPage {
	private string $template_path;

	public function __construct( string $template_path ) {
		$this->template_path = $template_path;
	}

	/**
	 * Get the capability required to view the admin page
	 *
	 * @return string
	 */
	public function get_capability(): string {
		return 'edit_posts';
	}

	/**
	 * Get the title of the admin page in the WordPress admin menu
	 *
	 * @return string
	 */
	public function get_menu_title(): string {
		return ''; // Must override
	}

	/**
	 * Get the title of the admin page
	 *
	 * @return string
	 */
	public function get_page_title(): string {
		return ''; // Must override
	}

	/**
	 * Get the parent slug of the admin page
	 *
	 * @return string
	 */
	public function get_parent_slug(): string {
		return ''; // Must override
	}

	/**
	 * Get the slug used by the admin page
	 *
	 * @return string
	 */
	public function get_menu_slug(): string {
		return ''; // Must override
	}

	/**
	 * Get position of menu item
	 *
	 * @return int
	 */
	public function get_menu_position(): int {
		return 0; // Must override
	}

	/**
	 * Get menu icon URL or name
	 *
	 * @return string
	 */
	public function get_menu_icon_url(): string {
		return ''; // Must override
	}

	/**
	 * Renders the given template if it's readable
	 *
	 * @param string $template
	 */
	private function render_template( string $template ) {
		$template_file = $this->template_path . '/' . $template . '.php';

		if ( ! is_readable( $template_file ) ) {
			return;
		}

		include $template_file;
	}

	/**
	 * Renders custom admin page
	 */
	public function render() {
		$this->render_template( $this->get_menu_slug() );
	}
}
