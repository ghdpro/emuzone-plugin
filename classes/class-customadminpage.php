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
	private function render_template( string $template, array $vars = array() ): void {
		$template_file = $this->template_path . '/' . $template . '.php';

		if ( ! is_readable( $template_file ) ) {
			return;
		}

		// Calling functions can pass an array with variables to be injected in current scope
		extract( $vars );
		include $template_file;
	}

	/**
	 * Renders custom admin page
	 */
	public function render( array $vars = array() ): void {
		$this->render_template( $this->get_menu_slug(), $vars );
	}

	/**
	 * Render custom template for custom admin page
	 */
	protected function render_custom( $template, $vars ): void {
		$this->render_template( $this->get_menu_slug() . '-' . $template, $vars );
	}

	/**
	 * Sets message (for display after redirect)
	 */
	public function set_message( string $message_type, string $message ): void {
		$transient = $this->get_menu_slug() . '_message_' . get_current_user_id();
		set_transient( $transient, array( 'type' => $message_type,
		                                  'message' => $message),
			DAY_IN_SECONDS );
	}

	/**
	 * Displays message (if one is set). Removes message after display.
	 */
	public function display_message(): void {
		$transient = $this->get_menu_slug() . '_message_' . get_current_user_id();
		$message = get_transient( $transient );
		if ( $message !== false ) {
			wp_admin_notice( $message['message'], array(
				'type' => $message['type'],
				'dismissible' => true,
				'additional_classes' => array( 'notice-alt' ),
			));
			delete_transient( $transient );
		}
	}
}
