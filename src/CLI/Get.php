<?php
/**
 * `wp <slug> config get` — read settings.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use WP_CLI;

/**
 * Get one or all settings.
 *
 * ## OPTIONS
 *
 * [<key>]
 * : Setting key in dot notation (e.g. cache.ttl) or module name (e.g. cache).
 *
 * [--network]
 * : Operate on network-scoped settings. Errors when no network Settings is registered.
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
 *     # Get all settings (per-site by default).
 *     wp myplugin config get
 *
 *     # Get all settings from the network scope.
 *     wp myplugin config get --network
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
 * @since 2.5.0
 */
final class Get extends Command {

	/**
	 * Execute the subcommand.
	 *
	 * @since 2.5.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$settings    = $this->resolve( $assoc_args );
		$key         = $args[0] ?? null;
		$format      = $assoc_args['format'] ?? 'table';
		$show_source = isset( $assoc_args['show-source'] );

		if ( null !== $key ) {
			$value = $settings->get( $key );

			if ( null === $value ) {
				WP_CLI::error( "Setting '{$key}' not found." );
			}

			$has_dot = strpos( $key, '.' ) !== false;

			if ( $has_dot || ! is_array( $value ) ) {
				if ( 'table' === $format ) {
					WP_CLI::line( $this->stringify( $value ) );
				} else {
					WP_CLI::print_value( $value, array( 'format' => $format ) );
				}
				return;
			}

			$rows    = $this->flatten_settings( array( $key => $value ), $show_source, $settings );
			$columns = $show_source ? array( 'key', 'value', 'source' ) : array( 'key', 'value' );

			$this->output_items( $rows, array( $key => $value ), $format, $columns );
			return;
		}

		$all = $settings->get();

		if ( ! is_array( $all ) ) {
			WP_CLI::error( 'No settings found.' );
		}

		$rows    = $this->flatten_settings( $all, $show_source, $settings );
		$columns = $show_source ? array( 'key', 'value', 'source' ) : array( 'key', 'value' );

		$this->output_items( $rows, $all, $format, $columns );
	}
}
