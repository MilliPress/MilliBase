<?php
/**
 * Config file read/write/sync for pre-WordPress settings access.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Manages the PHP config file that syncs settings for pre-WordPress access.
 *
 * @since 1.0.0
 */
final class ConfigFile {

	/**
	 * The directory where config files are stored.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $directory;

	/**
	 * The sanitized domain identifier.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $domain;

	/**
	 * The option name (used in the file header comment).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $option_name;

	/**
	 * Create a new ConfigFile instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $directory   The directory for config files.
	 * @param string $domain      The sanitized domain identifier.
	 * @param string $option_name The option name.
	 */
	public function __construct( string $directory, string $domain, string $option_name ) {
		$this->directory   = rtrim( $directory, '/' ) . '/';
		$this->domain      = $domain;
		$this->option_name = $option_name;
	}

	/**
	 * Read settings from the config file.
	 *
	 * This may run before WordPress is loaded (standalone mode),
	 * so it uses native PHP file functions only.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function read( ?string $module = null ): array {
		$file = $this->get_file_path();

		if ( ! file_exists( $file ) ) {
			return array();
		}

		$config = include $file;

		if ( ! is_array( $config ) ) {
			return array();
		}

		if ( $module ) {
			return isset( $config[ $module ] ) ? array( $module => (array) $config[ $module ] ) : array();
		}

		return $config;
	}

	/**
	 * Write settings to the config file.
	 *
	 * Creates the target directory if it does not exist and invalidates
	 * OPcache for the written file. Only called from WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings The settings to write.
	 *
	 * @return bool True if written successfully.
	 */
	public function write( array $settings ): bool {
		if ( ! is_dir( $this->directory ) ) {
			wp_mkdir_p( $this->directory );
		}

		$file = $this->get_file_path();

		$content  = "<?php\n";
		$content .= "// Auto-generated configuration for {$this->option_name}\n";
		$content .= "defined( 'ABSPATH' ) || exit;\n\n";
		$content .= 'return ' . $this->export_array( $settings ) . ";\n";

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$result = $wp_filesystem->put_contents( $file, $content, FS_CHMOD_FILE );

		// Invalidate OPcache for this file.
		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $file, true );
		}

		return (bool) $result;
	}

	/**
	 * Delete the config file.
	 *
	 * Only called from WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if deleted successfully.
	 */
	public function delete(): bool {
		$file = $this->get_file_path();

		if ( ! file_exists( $file ) ) {
			return false;
		}

		wp_delete_file( $file );

		return true;
	}

	/**
	 * Get the full file path for the config file.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_file_path(): string {
		$filename = $this->domain;

		if ( function_exists( 'sanitize_file_name' ) ) {
			$filename = sanitize_file_name( $filename );
		}

		return $this->directory . $filename . '.php';
	}

	/**
	 * Export a PHP value as a parseable string representation.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value  The value to export.
	 * @param int   $indent The current indentation level.
	 *
	 * @return string
	 */
	private function export_array( $value, int $indent = 0 ): string {
		if ( ! is_array( $value ) ) {
			if ( is_null( $value ) ) {
				return 'null';
			}
			if ( is_bool( $value ) ) {
				return $value ? 'true' : 'false';
			}
			if ( is_int( $value ) || is_float( $value ) ) {
				return (string) $value;
			}
			$string_value = is_string( $value ) ? $value : '';
			return "'" . addcslashes( $string_value, "'\\" ) . "'";
		}

		if ( empty( $value ) ) {
			return 'array()';
		}

		$pad     = str_repeat( '  ', $indent + 1 );
		$end_pad = str_repeat( '  ', $indent );
		$lines   = array();

		foreach ( $value as $k => $v ) {
			$exported_key = is_int( $k ) ? $k . ' => ' : "'" . addcslashes( (string) $k, "'\\" ) . "' => ";
			$lines[]      = $pad . $exported_key . $this->export_array( $v, $indent + 1 );
		}

		return "array(\n" . implode( ",\n", $lines ) . ",\n" . $end_pad . ')';
	}
}
