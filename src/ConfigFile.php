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
	 * Resolves the sanitized domain identifier at read/write time.
	 *
	 * Called on every file-path lookup so multisite blog switches are
	 * honoured rather than frozen at construction.
	 *
	 * @since 2.4.2
	 * @var \Closure(): string
	 */
	private \Closure $domain_resolver;

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
	 * The second argument may be a string (legacy: frozen domain) or a
	 * `Closure(): string` (preferred: resolved on every read/write so multisite
	 * blog switches are honoured). A string is wrapped as a constant closure.
	 *
	 * @since 1.0.0
	 * @since 2.4.3 Accepts a `Closure(): string` resolver in addition to a string.
	 *
	 * @param string $directory       The directory for config files.
	 * @param mixed  $domain_resolver Sanitized domain identifier (string) or a `Closure(): string` resolver; validated at runtime.
	 * @param string $option_name     The option name.
	 *
	 * @throws \InvalidArgumentException If $domain_resolver is neither a string nor a Closure.
	 */
	public function __construct( string $directory, $domain_resolver, string $option_name ) {
		if ( is_string( $domain_resolver ) ) {
			$domain          = $domain_resolver;
			$domain_resolver = static fn(): string => $domain;
		} elseif ( ! $domain_resolver instanceof \Closure ) {
			throw new \InvalidArgumentException(
				'ConfigFile $domain_resolver must be a string or Closure(): string.'
			);
		}

		$this->directory       = rtrim( $directory, '/' ) . '/';
		$this->domain_resolver = $domain_resolver;
		$this->option_name     = $option_name;
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

		/** @var array<string, array<string, mixed>> $config */
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

		// Temp file + rename.
		$temp = $file . '.tmp.' . uniqid( '', true );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Pre-WordPress config file needs direct access.
		$written = file_put_contents( $temp, $content );

		if ( strlen( $content ) !== $written ) {
			if ( false !== $written ) {
				wp_delete_file( $temp );
			}
			return false;
		}

		$mode = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Companion to the direct write above.
		chmod( $temp, $mode );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic swap; WP_Filesystem::move() is not atomic.
		if ( ! rename( $temp, $file ) ) {
			wp_delete_file( $temp );
			return false;
		}

		// Invalidate OPcache for this file.
		if ( function_exists( 'opcache_invalidate' ) ) {
			opcache_invalidate( $file, true );
		}

		return true;
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
		return $this->directory . ( $this->domain_resolver )() . '.php';
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
