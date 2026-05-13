<?php
/**
 * `wp <slug> config restore` — restore settings from the most recent backup.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use WP_CLI;

/**
 * Restore settings from the most recent backup.
 *
 * ## EXAMPLES
 *
 *     wp myplugin config restore
 *
 * @since 2.5.0
 */
final class Restore extends Command {

	/**
	 * Execute the subcommand.
	 *
	 * @since 2.5.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->settings->restore_backup() ) {
			WP_CLI::error( 'No backup found or backup has expired.' );
		}

		WP_CLI::success( 'Settings restored from backup.' );
	}
}
