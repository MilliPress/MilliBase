<?php
/**
 * `wp <slug> config set` — write a single setting value.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use MilliBase\Settings;
use WP_CLI;

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
 * [--network]
 * : Operate on network-scoped settings. Errors when no network Settings is registered.
 *
 * ## EXAMPLES
 *
 *     wp myplugin config set cache.ttl 3600
 *     wp myplugin config set cache.enabled true
 *     wp myplugin config set quota.max 100 --network
 *
 * @since 2.5.0
 */
final class Set extends Command {

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
		$settings = $this->resolve( $assoc_args );
		$key      = $args[0];
		$value    = Settings::coerce_value( $args[1] );

		$parts       = explode( '.', $key, 2 );
		$setting_key = $parts[1] ?? '';

		if ( '' === $setting_key ) {
			WP_CLI::error( "Invalid key '{$key}'. Key must use dot notation (module.key)." );
		}

		$source = $settings->get_source( $parts[0], $setting_key );

		if ( 'constant' === $source ) {
			WP_CLI::error( "Cannot set '{$key}' because it is defined as a constant." );
		}

		if ( ! $settings->set( $key, $value ) ) {
			WP_CLI::error( "Failed to set '{$key}'." );
		}

		$display_value = ( strpos( $setting_key, 'enc_' ) === 0 )
			? '***'
			: $this->stringify( $value );

		WP_CLI::success( "Set '{$key}' to \"{$display_value}\"." );
	}
}
