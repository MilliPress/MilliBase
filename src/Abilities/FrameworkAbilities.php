<?php
/**
 * Framework-level ability factory — wraps Settings operations as abilities.
 *
 * @package MilliBase
 * @author  Philipp Wellmer & Vedran <hello@millipress.com>
 */

namespace MilliBase\Abilities;

use MilliBase\Settings;
use MilliBase\Settings\Group;

/**
 * Builds ability config entries that wrap the built-in Settings
 * operations (export, reset, backup, restore). See docs/02-usage/05-abilities.md.
 *
 * @since 2.5.0
 */
final class FrameworkAbilities {

	/**
	 * Build the four standard settings abilities for a plugin.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings|Group $settings Cross-prefix tolerant; bound into closures.
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings( $settings ): array {
		return array(
			self::export( $settings ),
			self::reset( $settings ),
			self::backup( $settings ),
			self::restore( $settings ),
		);
	}

	/**
	 * Build the `settings-export` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings|Group $settings The Settings (or Group) to read from.
	 * @return array<string, mixed>
	 */
	private static function export( $settings ): array {
		return array(
			'id'            => 'settings-export',
			'label'         => __( 'Export settings', 'millibase' ),
			'description'   => __( 'Export the plugin settings as an object keyed by module name. Pass the optional `module` argument to limit which modules are populated; the response shape is always module → settings. Encrypted values are stripped unless `include_encrypted` is true.', 'millibase' ),
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				$module            = self::input_string( $input, 'module' );
				$include_encrypted = self::input_bool( $input, 'include_encrypted' );
				return $settings->export( $module, $include_encrypted );
			},
			'input_schema'  => array(
				'type'       => 'object',
				'properties' => array(
					'module'            => array( 'type' => 'string' ),
					'include_encrypted' => array( 'type' => 'boolean' ),
				),
			),
			'output_schema' => array(
				'type'                 => 'object',
				'description'          => __( 'Settings keyed by module name; each module is an object of key/value pairs.', 'millibase' ),
				'additionalProperties' => array(
					'type' => 'object',
				),
			),
			'meta'          => array(
				'annotations' => array(
					'readonly' => true,
				),
			),
		);
	}

	/**
	 * Build the `settings-reset` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings|Group $settings The Settings (or Group) to mutate.
	 * @return array<string, mixed>
	 */
	private static function reset( $settings ): array {
		return array(
			'id'            => 'settings-reset',
			'label'         => __( 'Reset settings to defaults', 'millibase' ),
			'description'   => __( 'Reset the plugin settings to their defaults. An automatic backup is created before the reset.', 'millibase' ),
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				$module = self::input_string( $input, 'module' );
				$settings->backup( $module );
				return array( 'success' => $settings->reset( $module ) );
			},
			'input_schema'  => array(
				'type'       => 'object',
				'properties' => array(
					'module' => array( 'type' => 'string' ),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
				),
				'required'   => array( 'success' ),
			),
			'meta'          => array(
				'annotations' => array(
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Build the `settings-backup` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings|Group $settings The Settings (or Group) to back up.
	 * @return array<string, mixed>
	 */
	private static function backup( $settings ): array {
		return array(
			'id'            => 'settings-backup',
			'label'         => __( 'Back up settings', 'millibase' ),
			'description'   => __( 'Take a backup of the current plugin settings. The backup expires after 12 hours.', 'millibase' ),
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				$module = self::input_string( $input, 'module' );
				$settings->backup( $module );
				return array( 'success' => $settings->has_backup() );
			},
			'input_schema'  => array(
				'type'       => 'object',
				'properties' => array(
					'module' => array( 'type' => 'string' ),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
				),
				'required'   => array( 'success' ),
			),
			'meta'          => array(
				'annotations' => array(
					'idempotent' => true,
				),
			),
		);
	}

	/**
	 * Build the `settings-restore` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings|Group $settings The Settings (or Group) to restore into.
	 * @return array<string, mixed>
	 */
	private static function restore( $settings ): array {
		return array(
			'id'            => 'settings-restore',
			'label'         => __( 'Restore settings from backup', 'millibase' ),
			'description'   => __( 'Restore the most recent settings backup. Returns success: false when no backup is available.', 'millibase' ),
			'callback'      => static function () use ( $settings ): array {
				return array( 'success' => $settings->restore_backup() );
			},
			'input_schema'  => array(
				'type'                 => 'object',
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
				),
				'required'   => array( 'success' ),
			),
			'meta'          => array(
				'annotations' => array(
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Extract a string field from ability input, returning null when absent or empty.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed  $input The ability input as received by execute_callback.
	 * @param string $key   The field name to extract.
	 * @return string|null
	 */
	private static function input_string( $input, string $key ): ?string {
		if ( ! is_array( $input ) ) {
			return null;
		}
		$value = $input[ $key ] ?? null;
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		return $value;
	}

	/**
	 * Extract a boolean field from ability input — uses FILTER_VALIDATE_BOOLEAN to handle stringified booleans.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed  $input The ability input as received by execute_callback.
	 * @param string $key   The field name to extract.
	 * @return bool
	 */
	private static function input_bool( $input, string $key ): bool {
		if ( ! is_array( $input ) ) {
			return false;
		}
		return (bool) filter_var( $input[ $key ] ?? false, FILTER_VALIDATE_BOOLEAN );
	}
}
