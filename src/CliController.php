<?php
/**
 * WP-CLI controller for settings management.
 *
 * Registers a `config` subcommand with get, set, reset, backup, restore,
 * export, import, and status — all backed by the same Settings instance
 * that powers the REST API and admin UI.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

use WP_CLI;

/**
 * Registers WP-CLI commands so every plugin built on MilliBase
 * automatically gets a full settings CLI under `wp <slug> config`.
 *
 * @since 1.2.0
 */
final class CliController {

	/**
	 * The settings configuration.
	 *
	 * @since 1.2.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The Settings instance.
	 *
	 * @since 1.2.0
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Create a new CliController instance.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, mixed> $config   The settings configuration.
	 * @param Settings             $settings The settings instance.
	 */
	public function __construct( array $config, Settings $settings ) {
		$this->config   = $config;
		$this->settings = $settings;
	}

	/**
	 * Get a string value from the config array.
	 *
	 * @param string $key      The config key.
	 * @param string $fallback The fallback value.
	 *
	 * @return string
	 */
	private function config_string( string $key, string $fallback = '' ): string {
		$value = $this->config[ $key ] ?? $fallback;
		return is_string( $value ) ? $value : $fallback;
	}

	/**
	 * Register the WP-CLI command if WP-CLI is available.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		$slug = $this->config_string( 'slug', 'millibase' );

		WP_CLI::add_command( "{$slug} config", $this );
	}

	/**
	 * Get one or all settings.
	 *
	 * ## OPTIONS
	 *
	 * [<key>]
	 * : Setting key in dot notation (e.g. cache.ttl) or module name (e.g. cache).
	 *
	 * [--show-source]
	 * : Show where each value comes from (constant, file, db, default).
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get all settings.
	 *     wp myplugin config get
	 *
	 *     # Get all settings for a module.
	 *     wp myplugin config get cache
	 *
	 *     # Get a specific value.
	 *     wp myplugin config get cache.ttl
	 *
	 *     # Get settings with source info.
	 *     wp myplugin config get --show-source
	 *
	 *     # Get a value for scripting.
	 *     wp myplugin config get cache.ttl --format=json
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		$key         = $args[0] ?? null;
		$format      = $assoc_args['format'] ?? 'table';
		$show_source = isset( $assoc_args['show-source'] );

		if ( null !== $key ) {
			$value = $this->settings->get( $key );

			if ( null === $value ) {
				WP_CLI::error( "Setting '{$key}' not found." );
			}

			$has_dot = strpos( $key, '.' ) !== false;

			// Single scalar value via dot-key — output raw value.
			if ( $has_dot || ! is_array( $value ) ) {
				if ( 'table' === $format ) {
					// Single value: output raw for humans.
					WP_CLI::line( $this->stringify( $value ) );
				} else {
					// JSON/yaml/csv: output raw value for scripting.
					WP_CLI::print_value( $value, array( 'format' => $format ) );
				}
				return;
			}

			// Module key (e.g. "cache") — flatten to table rows.
			$rows    = $this->flatten_settings( array( $key => $value ), $show_source );
			$columns = $show_source ? array( 'key', 'value', 'source' ) : array( 'key', 'value' );

			$this->output_items( $rows, array( $key => $value ), $format, $columns );
			return;
		}

		// All settings — output as table.
		$all = $this->settings->get();

		if ( ! is_array( $all ) ) {
			WP_CLI::error( 'No settings found.' );
		}

		$rows    = $this->flatten_settings( $all, $show_source );
		$columns = $show_source ? array( 'key', 'value', 'source' ) : array( 'key', 'value' );

		$this->output_items( $rows, $all, $format, $columns );
	}

	/**
	 * Set a single setting value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Setting key in dot notation (e.g. cache.ttl).
	 *
	 * <value>
	 * : The value to set. Strings "true", "false", "null" and numeric
	 *   strings are automatically coerced to native types.
	 *
	 * ## EXAMPLES
	 *
	 *     wp myplugin config set cache.ttl 3600
	 *     wp myplugin config set cache.enabled true
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function set( array $args, array $assoc_args ): void {
		$key   = $args[0];
		$value = Settings::coerce_value( $args[1] );

		$parts       = explode( '.', $key, 2 );
		$setting_key = $parts[1] ?? '';
		$source      = $this->settings->get_source( $parts[0], $setting_key );

		if ( 'constant' === $source ) {
			WP_CLI::error( "Cannot set '{$key}' because it is defined as a constant." );
		}

		if ( ! $this->settings->set( $key, $value ) ) {
			WP_CLI::error( "Failed to set '{$key}'. Key must use dot notation (module.key)." );
		}

		// Mask encrypted field values in output.
		$display_value = ( '' !== $setting_key && strpos( $setting_key, 'enc_' ) === 0 )
			? '***'
			: $this->stringify( $value );

		WP_CLI::success( "Set '{$key}' to \"{$display_value}\"." );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * Creates an automatic backup before resetting.
	 *
	 * ## OPTIONS
	 *
	 * [--module=<module>]
	 * : Reset only a specific module instead of all settings.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp myplugin config reset
	 *     wp myplugin config reset --module=cache --yes
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function reset( array $args, array $assoc_args ): void {
		$module = $assoc_args['module'] ?? null;
		$target = null !== $module ? "module '{$module}'" : 'all settings';

		WP_CLI::confirm( "Reset {$target} to defaults?", $assoc_args );

		$this->settings->backup( $module );
		$this->settings->reset( $module );

		WP_CLI::success( "Reset {$target} to defaults. A backup was created automatically." );
	}

	/**
	 * Create a backup of current settings.
	 *
	 * Backup expires after 12 hours.
	 *
	 * ## EXAMPLES
	 *
	 *     wp myplugin config backup
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function backup( array $args, array $assoc_args ): void {
		$this->settings->backup();
		WP_CLI::success( 'Backup created. Expires in 12 hours.' );
	}

	/**
	 * Restore settings from the most recent backup.
	 *
	 * ## EXAMPLES
	 *
	 *     wp myplugin config restore
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function restore( array $args, array $assoc_args ): void {
		if ( ! $this->settings->restore_backup() ) {
			WP_CLI::error( 'No backup found or backup has expired.' );
		}

		WP_CLI::success( 'Settings restored from backup.' );
	}

	/**
	 * Export settings as JSON.
	 *
	 * Outputs JSON to stdout by default. Use --file to write directly to a file.
	 *
	 * ## OPTIONS
	 *
	 * [--module=<module>]
	 * : Export only a specific module.
	 *
	 * [--include-encrypted]
	 * : Include decrypted values of encrypted fields.
	 *
	 * [--file=<path>]
	 * : Write output to a file instead of stdout.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export to stdout.
	 *     wp myplugin config export
	 *
	 *     # Export directly to a file.
	 *     wp myplugin config export --file=settings.json
	 *
	 *     # Export a single module.
	 *     wp myplugin config export --module=cache
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		$module            = $assoc_args['module'] ?? null;
		$include_encrypted = isset( $assoc_args['include-encrypted'] );
		$file              = $assoc_args['file'] ?? null;

		$data = $this->settings->export( $module, $include_encrypted );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			WP_CLI::error( 'Failed to encode settings as JSON.' );
		}

		if ( null !== $file ) {
			if ( false === file_put_contents( $file, $json . "\n" ) ) {
				WP_CLI::error( "Failed to write to '{$file}'." );
			}
			WP_CLI::success( "Settings exported to '{$file}'." );
			return;
		}

		WP_CLI::line( $json );
	}

	/**
	 * Import settings from a JSON file.
	 *
	 * Creates an automatic backup before importing.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : Path to the JSON file to import.
	 *
	 * [--merge]
	 * : Merge with existing settings instead of replacing.
	 * ---
	 * default: true
	 * ---
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp myplugin config import --file=settings.json
	 *     wp myplugin config import --file=settings.json --no-merge --yes
	 *
	 * @since 1.2.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		$file = $assoc_args['file'] ?? null;
		/** @var bool $merge */
		$merge = WP_CLI\Utils\get_flag_value( $assoc_args, 'merge', true );

		if ( null === $file || '' === $file ) {
			WP_CLI::error( 'Usage: wp <slug> config import --file=<path>' );
		}

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			WP_CLI::error( "File not found or not readable: {$file}" );
		}

		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			WP_CLI::error( "Failed to read file: {$file}" );
		}

		/** @var array<string, mixed>|null $data */
		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON in file.' );
		}

		if ( ! $merge ) {
			WP_CLI::confirm( 'Import will replace all existing settings. Continue?', $assoc_args );
		}

		// Create backup before import.
		$this->settings->backup();

		if ( ! $this->settings->import( $data, (bool) $merge ) ) {
			WP_CLI::error( 'Import failed. No valid modules found in the provided data.' );
		}

		$count = count( $data );
		$mode  = $merge ? 'merged' : 'replaced';
		WP_CLI::success( "Imported {$count} module(s) ({$mode})." );
	}

	/**
	 * Output items in the requested format.
	 *
	 * For JSON format, outputs the raw nested data structure (not row objects).
	 * For table/yaml/csv, uses WP_CLI's built-in formatter.
	 *
	 * @param array<int, array<string, string>> $items    The rows to display.
	 * @param mixed                             $raw_data The original nested data (for JSON output).
	 * @param string                            $format   The output format.
	 * @param array<int, string>                $columns  The column names.
	 * @return void
	 */
	private function output_items( array $items, $raw_data, string $format, array $columns ): void {
		if ( 'json' === $format ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$json = json_encode( $raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false !== $json ? $json : '{}' );
			return;
		}

		WP_CLI\Utils\format_items( $format, $items, $columns );
	}

	/**
	 * Flatten nested settings into dot-notation rows for display.
	 *
	 * @param array<string, mixed> $data        Nested settings array.
	 * @param bool                 $show_source Whether to include a source column.
	 * @return array<int, array<string, string>>
	 */
	private function flatten_settings( array $data, bool $show_source ): array {
		$rows = array();

		foreach ( $data as $module => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				$row = array(
					'key'   => $module . '.' . $key,
					'value' => $this->stringify( $value ),
				);

				if ( $show_source ) {
					$row['source'] = $this->settings->get_source( (string) $module, (string) $key );
				}

				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Convert a value to a human-readable string for CLI output.
	 *
	 * @param mixed $value The value to convert.
	 * @return string
	 */
	private function stringify( $value ): string {
		if ( null === $value ) {
			return 'null';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_array( $value ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$json = json_encode( $value, JSON_UNESCAPED_SLASHES );
			return false !== $json ? $json : '(array)';
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}
		return '';
	}
}
