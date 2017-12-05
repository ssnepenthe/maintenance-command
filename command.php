<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'maintenance',
	/**
	 * Handle maintenance mode for a site.
	 */
	new class extends WP_CLI_Command {
		/**
		 * Disable maintenance mode.
		 *
		 * @return void
		 */
		public function disable() {
			if ( ! $this->delete_file() ) {
				WP_CLI::error( 'Something went wrong... Please try again' );
			}

			WP_CLI::success( 'Maintenance mode disabled' );
		}

		/**
		 * Enable maintenance mode.
		 *
		 * @return void
		 */
		public function enable() {
			// This causes maintenance mode to be extended if already active by deleting and
			// re-creating the maintenance file. Would it be better to bail here instead?
			if ( $this->file_exists() ) {
				$this->delete_file();
			}

			if ( ! $this->create_file() ) {
				WP_CLI::error( 'Something went wrong... Please try again' );
			}

			WP_CLI::success( 'Maintenance mode enabled' );
		}

		/**
		 * Display the current maintenance mode status.
		 *
		 * @return void
		 */
		public function status() {
			WP_CLI::log( sprintf(
				'Maintenance mode is currently %s',
				$this->is_enabled() ? 'enabled' : 'disabled'
			) );
		}

		/**
		 * Toggle maintenance mode.
		 *
		 * @return void
		 */
		public function toggle() {
			$this->{$this->is_enabled() ? 'disable' : 'enable'}();
		}

		/**
		 * Create the ".maintenance" file.
		 *
		 * @return boolean
		 */
		protected function create_file() {
			return $this->get_filesystem()->put_contents(
				$this->get_file_path(),
				$this->create_file_contents(),
				FS_CHMOD_FILE
			);
		}

		/**
		 * Generate the contents for the ".maintenance" file.
		 *
		 * @return string
		 */
		protected function create_file_contents() {
			return '<?php $upgrading = ' . time() . '; ?>';
		}

		/**
		 * Delete the ".maintenance" file.
		 *
		 * @return boolean
		 */
		protected function delete_file() {
			return $this->get_filesystem()->delete( $this->get_file_path() );
		}

		/**
		 * Check whether the ".maintenance" file exists.
		 *
		 * @return boolean
		 */
		protected function file_exists() {
			return $this->get_filesystem()->exists( $this->get_file_path() );
		}

		/**
		 * Get the path for the ".maintenance" file.
		 *
		 * @return string
		 */
		protected function get_file_path() {
			return $this->get_filesystem()->abspath() . '.maintenance';
		}

		/**
		 * Get the WordPress filesystem instance.
		 *
		 * @return null|\WP_Filesystem_Base
		 */
		protected function get_filesystem() {
			if ( ! isset( $GLOBALS['wp_filesystem'] ) ) {
				WP_Filesystem();
			}

			return $GLOBALS['wp_filesystem'];
		}

		/**
		 * Check whether maintenance mode is currently enabled.
		 *
		 * Technically we should also check the return value of 'enable_maintenance_mode' filter.
		 * Unfortunately I believe this is impossible - WP-CLI filters this to always return false
		 * in php/WP_CLI/runner.php to make sure commands can run regardless of maintenance mode.
		 *
		 * @return boolean
		 */
		protected function is_enabled() {
			global $upgrading;

			if ( ! $this->file_exists() ) {
				return false;
			}

			include $this->get_file_path();

			// Enabled unless timestamp is older than 10 minutes.
			return ( time() - $upgrading ) < 600;
		}
	}
);
