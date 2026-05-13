<?php
/**
 * Framework-level ability factory — wraps Settings operations as abilities.
 *
 * @package MilliBase
 * @author  Philipp Wellmer & Vedran <hello@millipress.com>
 */

namespace MilliBase\Abilities;

use MilliBase\Settings;

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
	 * When the bound Settings is network-scoped, every ability id gets a
	 * `network-` prefix and the label/description name the scope explicitly
	 * so the network surface never collides with the per-site surface on
	 * the same plugin slug and consumers can tell the two apart.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings Cross-prefix tolerant; bound into closures.
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings( $settings ): array {
		$is_network = method_exists( $settings, 'is_network' ) && $settings->is_network();

		return array(
			self::export( $settings, $is_network ),
			self::reset( $settings, $is_network ),
			self::backup( $settings, $is_network ),
			self::restore( $settings, $is_network ),
		);
	}

	/**
	 * Build the `settings-export` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings   The Settings to read from.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function export( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-export',
			'label'         => $is_network
				? __( 'Export Network Settings', 'millibase' )
				: __( 'Export Settings', 'millibase' ),
			'description'   => $is_network
				? __( 'Export the network settings as an object keyed by module name (site settings are not included). Pass the optional `module` argument to limit which modules are populated; the response shape is always module → settings. Encrypted values are stripped unless `include_encrypted` is true.', 'millibase' )
				: __( 'Export the site settings as an object keyed by module name. Pass the optional `module` argument to limit which modules are populated; the response shape is always module → settings. Encrypted values are stripped unless `include_encrypted` is true.', 'millibase' ),
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
	 * @param Settings $settings   The Settings to mutate.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function reset( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-reset',
			'label'         => $is_network
				? __( 'Reset Network Settings to Defaults', 'millibase' )
				: __( 'Reset Settings to Defaults', 'millibase' ),
			'description'   => $is_network
				? __( 'Reset the network settings to their defaults (site settings are not affected). An automatic backup is created before the reset.', 'millibase' )
				: __( 'Reset the site settings to their defaults. An automatic backup is created before the reset.', 'millibase' ),
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				$module = self::input_string( $input, 'module' );
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
	 * @param Settings $settings   The Settings to back up.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function backup( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-backup',
			'label'         => $is_network
				? __( 'Back Up Network Settings', 'millibase' )
				: __( 'Back Up Settings', 'millibase' ),
			'description'   => $is_network
				? __( 'Take a backup of the current network settings (site settings are not included). The backup expires after 12 hours.', 'millibase' )
				: __( 'Take a backup of the current site settings. The backup expires after 12 hours.', 'millibase' ),
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
	 * @param Settings $settings   The Settings to restore into.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function restore( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-restore',
			'label'         => $is_network
				? __( 'Restore Network Settings from Backup', 'millibase' )
				: __( 'Restore Settings from Backup', 'millibase' ),
			'description'   => $is_network
				? __( 'Restore the most recent network settings backup (site settings are not affected). Returns success: false when no backup is available.', 'millibase' )
				: __( 'Restore the most recent site settings backup. Returns success: false when no backup is available.', 'millibase' ),
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
