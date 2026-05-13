<?php
/**
 * `wp <slug> config reset` — reset settings to defaults.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use WP_CLI;

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
 * [--network]
 * : Operate on network-scoped settings. Errors when no network Settings is registered.
 *
 * [--yes]
 * : Skip the confirmation prompt.
 *
 * ## EXAMPLES
 *
 *     wp myplugin config reset
 *     wp myplugin config reset --module=cache --yes
 *     wp myplugin config reset --network --yes
 *
 * @since 2.5.0
 */
final class Reset extends Command {

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
		$module   = $assoc_args['module'] ?? null;
		$target   = null !== $module ? "module '{$module}'" : 'all settings';

		WP_CLI::confirm( "Reset {$target} to defaults?", $assoc_args );

		$settings->backup( $module );
		$settings->reset( $module );

		WP_CLI::success( "Reset {$target} to defaults. A backup was created automatically." );
	}
}
