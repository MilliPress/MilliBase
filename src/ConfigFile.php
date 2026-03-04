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
	 * OPcache for the written file.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings The settings to write.
	 *
	 * @return bool True if written successfully.
	 */
	public function write( array $settings ): bool {
		if ( ! is_dir( $this->directory ) ) {
			if ( function_exists( 'wp_mkdir_p' ) ) {
				wp_mkdir_p( $this->directory );
			} else {
				mkdir( $this->directory, 0755, true );
			}
		}

		$file = $this->get_file_path();

		$content  = "<?php\n";
		$content .= "// Auto-generated configuration for {$this->option_name}\n";
		$content .= "defined( 'ABSPATH' ) || exit;\n\n";
		$content .= 'return ' . var_export( $settings, true ) . ";\n";

		$result = file_put_contents( $file, $content );

		// Invalidate OPcache for this file.
		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $file, true );
		}

		return false !== $result;
	}

	/**
	 * Delete the config file.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if deleted successfully.
	 */
	public function delete(): bool {
		$file = $this->get_file_path();

		if ( file_exists( $file ) ) {
			return unlink( $file );
		}

		return false;
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
}
