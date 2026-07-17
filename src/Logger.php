<?php
/**
 * Channel-prefixed logger with level-based gating.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Writes prefixed log entries to the PHP error log.
 *
 * Each consuming plugin creates its own instance with a channel name,
 * so every line in debug.log is attributable ("[MilliCache] ...") and
 * filterable by a future log viewer. Error and warning entries always
 * log (they signal real incidents such as a lost storage connection);
 * debug entries are gated behind WP_DEBUG.
 *
 * Centralizes the error_log() call so consuming plugins contain no
 * direct error_log() usage, keeping PHPCS/Plugin Check findings to a
 * single, auditable location. Observers (e.g. a dashboard log view)
 * can capture entries via the `millibase_log` action.
 *
 * Safe to use before WordPress has fully loaded (e.g. from an
 * advanced-cache.php drop-in): WordPress functions are feature-detected
 * and skipped when unavailable.
 *
 * @since 2.7.0
 */
final class Logger {

	/**
	 * Level for failures that need attention regardless of debug mode.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	public const ERROR = 'error';

	/**
	 * Level for recoverable or degraded-operation notices.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	public const WARNING = 'warning';

	/**
	 * Level for developer diagnostics, only written when WP_DEBUG is on.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	public const DEBUG = 'debug';

	/**
	 * Channel name prepended to every entry, typically the plugin name.
	 *
	 * @since 2.7.0
	 *
	 * @var string
	 */
	private string $channel;

	/**
	 * Set up a logger for the given channel.
	 *
	 * @since 2.7.0
	 *
	 * @param string $channel Channel name, typically the plugin name (e.g. "MilliCache").
	 */
	public function __construct( string $channel ) {
		$this->channel = $channel;
	}

	/**
	 * Log an error. Always written.
	 *
	 * @since 2.7.0
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional structured context, appended as JSON.
	 *
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Log a warning. Always written.
	 *
	 * @since 2.7.0
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional structured context, appended as JSON.
	 *
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Log a diagnostic message. Only written when WP_DEBUG is enabled.
	 *
	 * @since 2.7.0
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional structured context, appended as JSON.
	 *
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Write a log entry.
	 *
	 * @since 2.7.0
	 *
	 * @param string               $level   One of the level constants.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional structured context, appended as JSON.
	 *
	 * @return void
	 */
	public function log( string $level, string $message, array $context = array() ): void {
		if ( self::DEBUG === $level && ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return;
		}

		$line = sprintf( '[%s] [%s] %s', $this->channel, $level, $message );

		if ( array() !== $context ) {
			$line .= ' ' . $this->encode( $context );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging chokepoint; error/warning entries signal real incidents, debug entries are gated behind WP_DEBUG above.
		error_log( $line );

		if ( function_exists( 'do_action' ) ) {
			/**
			 * Fires for every written log entry.
			 *
			 * Allows observers to mirror entries into additional sinks,
			 * e.g. a persistent store backing a dashboard log view.
			 *
			 * @since 2.7.0
			 *
			 * @param string               $channel Channel name.
			 * @param string               $level   Entry level.
			 * @param string               $message Log message.
			 * @param array<string, mixed> $context Structured context.
			 */
			do_action( 'millibase_log', $this->channel, $level, $message, $context );
		}
	}

	/**
	 * Encode context data as JSON for appending to a log line.
	 *
	 * @since 2.7.0
	 *
	 * @param array<string, mixed> $context Structured context.
	 *
	 * @return string JSON string, or an empty string when encoding fails.
	 */
	private function encode( array $context ): string {
		$json = function_exists( 'wp_json_encode' )
			? wp_json_encode( $context )
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for pre-WordPress contexts (advanced-cache.php) where wp_json_encode() is not loaded yet.
			: json_encode( $context );

		return is_string( $json ) ? $json : '';
	}
}
