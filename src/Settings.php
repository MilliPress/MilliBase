<?php
/**
 * Settings storage layer with dot-notation access, encryption, backup/restore,
 * and a standalone mode for reading config before WordPress loads.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Handles settings storage: option CRUD, dot-notation get/set with in-memory
 * caching, encryption, constants override, config file sync, backup/restore,
 * and import/export.
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * The option name in the database.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $option_name;

	/**
	 * Constant prefix for wp-config.php overrides (e.g. 'MC' → MC_STORAGE_HOST).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $constant_prefix;

	/**
	 * Whether sodium encryption is enabled for enc_* fields.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $encryption;

	/**
	 * Default settings extracted from the schema.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, mixed>>
	 */
	private array $defaults;

	/**
	 * The ConfigFile instance, or null if config files are disabled.
	 *
	 * @since 1.0.0
	 * @var ConfigFile|null
	 */
	private ?ConfigFile $config_file;

	/**
	 * Plugin slug for filter hook naming ({slug}_settings_defaults).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Whether this instance operates in standalone mode (no WordPress DB).
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $standalone;

	/**
	 * In-memory cache of resolved settings, keyed by cache key.
	 *
	 * Cleared on set(), reset(), and import().
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private array $resolved = array();

	/**
	 * The sanitized domain identifier for config file naming.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $domain;

	/**
	 * Create a new Settings instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration array.
	 *
	 * @throws \InvalidArgumentException If slug is empty.
	 */
	public function __construct( array $config ) {
		$slug       = $config['slug'] ?? '';
		$this->slug = is_string( $slug ) ? $slug : '';

		if ( '' === $this->slug ) {
			throw new \InvalidArgumentException( 'Settings requires a non-empty "slug" config value.' );
		}

		$default_option        = $this->slug;
		$option_name           = $config['option_name'] ?? $default_option;
		$this->option_name     = is_string( $option_name ) ? $option_name : $default_option;
		$constant_prefix       = $config['constant_prefix'] ?? '';
		$this->constant_prefix = strtoupper( is_string( $constant_prefix ) ? $constant_prefix : '' );
		$this->encryption      = (bool) ( $config['encryption'] ?? false );
		$this->defaults        = is_array( $config['defaults'] ?? null ) ? $config['defaults'] : array();
		$this->standalone      = (bool) ( $config['standalone'] ?? false );
		$this->domain          = $this->resolve_domain();

		// Initialize the config file handler if configured.
		if ( ! empty( $config['config_file'] ) && is_array( $config['config_file'] ) ) {
			$directory         = $config['config_file']['directory'] ?? '';
			$this->config_file = new ConfigFile(
				is_string( $directory ) ? $directory : '',
				$this->domain,
				$this->option_name
			);
		} else {
			$this->config_file = null;
		}

		$this->register_hooks();
	}

	/**
	 * Create a standalone Settings instance for use before WordPress loads.
	 *
	 * Reads from config files and constants only — no database access.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration array.
	 *
	 * @return self
	 */
	public static function standalone( array $config ): self {
		$config['standalone'] = true;
		return new self( $config );
	}

	/**
	 * Register WordPress hooks for option filtering, encryption, and config file sync.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( $this->standalone || ! function_exists( 'add_filter' ) ) {
			return;
		}

		// Merge defaults and strip constant-defined keys from the stored option.
		add_filter( 'option_' . $this->option_name, array( $this, 'filter_settings_by_constants' ) );
		add_filter( 'default_option_' . $this->option_name, array( $this, 'filter_settings_by_constants' ) );

		// Encryption hooks.
		if ( $this->encryption ) {
			add_filter( 'pre_update_option_' . $this->option_name, array( $this, 'encrypt_sensitive_settings_data' ), 0 );
			add_filter( 'option_' . $this->option_name, array( $this, 'decrypt_sensitive_settings_data' ), 0 );
		}

		// Config file sync hooks.
		if ( $this->config_file ) {
			add_action( 'add_option_' . $this->option_name, array( $this, 'on_add_option' ), 10, 2 );
			add_action( 'update_option_' . $this->option_name, array( $this, 'on_update_option' ), 10, 2 );
			add_action( 'delete_option', array( $this, 'on_delete_option' ) );
		}
	}

	/**
	 * Merge additional defaults into this instance.
	 *
	 * Used by the Manager to inject schema-extracted defaults (e.g. active-toggle
	 * keys) into a pre-built Settings instance that was created before the Schema
	 * was available. Existing keys are never overwritten.
	 *
	 * @since 1.0.3
	 *
	 * @param array<string, array<string, mixed>> $additional Additional defaults to merge.
	 *
	 * @return void
	 */
	public function merge_defaults( array $additional ): void {
		$this->defaults = array_replace_recursive( $additional, $this->defaults );
		$this->resolved = array();
	}

	// ─── Settings access ────────────────────────────────────────────────

	/**
	 * Get settings using optional dot notation.
	 *
	 * - `get()`                → all settings.
	 * - `get('cache')`         → all settings for the cache module.
	 * - `get('cache.ttl')`     → single value.
	 * - `get('cache.ttl', 60)` → single value with fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key      Dot notation key, module name, or null for all.
	 * @param mixed       $fallback Fallback value if key not found.
	 *
	 * @return ($key is null ? array<string, array<string, mixed>> : mixed)
	 */
	public function get( ?string $key = null, $fallback = null ) {
		$settings = $this->resolve();

		if ( null === $key ) {
			return $settings;
		}

		$keys  = explode( '.', $key );
		$value = $settings;

		foreach ( $keys as $k ) {
			if ( ! is_array( $value ) || ! array_key_exists( $k, $value ) ) {
				return $fallback;
			}
			$value = $value[ $k ];
		}

		return $value;
	}

	/**
	 * Set a value using dot notation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Dot notation key (e.g., 'cache.ttl'). Minimum 2 levels (module.key).
	 * @param mixed  $value The value to set.
	 *
	 * @return bool True if the value was set successfully.
	 */
	public function set( string $key, $value ): bool {
		$keys = explode( '.', $key );

		if ( count( $keys ) < 2 ) {
			return false;
		}

		$module   = array_shift( $keys );
		$settings = $this->resolve( null, true );

		if ( ! isset( $settings[ $module ] ) ) {
			$settings[ $module ] = array();
		}

		$ref      = &$settings[ $module ];
		$last_key = array_pop( $keys );

		foreach ( $keys as $k ) {
			if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
				$ref[ $k ] = array();
			}
			$ref = &$ref[ $k ];
		}

		$ref[ $last_key ] = $value;

		$this->resolved = array();

		return update_option( $this->option_name, $settings );
	}

	// ─── Settings resolution ────────────────────────────────────────────

	/**
	 * Resolve merged settings from all sources with priority hierarchy.
	 *
	 * Priority: Constants > Config File > Database > Defaults.
	 * Results are cached in memory for the current request.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module         Specific module to retrieve.
	 * @param bool        $skip_constants Whether to skip constants.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function resolve( ?string $module = null, bool $skip_constants = false ): array {
		$cache_key = ( $module ?? '__all__' ) . ( $skip_constants ? ':raw' : '' );

		if ( isset( $this->resolved[ $cache_key ] ) ) {
			return $this->resolved[ $cache_key ];
		}
		$settings = $this->get_default_settings( $module );

		// Merge from config file or DB.
		$file_settings   = $this->config_file ? $this->config_file->read( $module ) : array();
		$config_settings = ! empty( $file_settings ) ? $file_settings : $this->get_settings_from_db( $module );

		foreach ( $config_settings as $module_key => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( isset( $settings[ $module_key ] ) && array_key_exists( $key, $settings[ $module_key ] ) ) {
					$settings[ $module_key ][ $key ] = $value;
				}
			}
		}

		// Constants override.
		if ( ! $skip_constants && '' !== $this->constant_prefix ) {
			$constant_settings = $this->get_settings_from_constants( $module );
			foreach ( $constant_settings as $module_key => $module_settings ) {
				if ( ! is_array( $module_settings ) ) {
					continue;
				}
				foreach ( $module_settings as $key => $value ) {
					$settings[ $module_key ][ $key ] = $value;
				}
			}
		}

		// Inject the host module for full retrievals.
		if ( null === $module ) {
			$settings['host'] = array( 'domain' => $this->domain );
		}

		$this->resolved[ $cache_key ] = $settings;

		return $settings;
	}

	/**
	 * Return defaults with add-on filters applied (cached in $resolved).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function defaults(): array {
		if ( isset( $this->resolved['__defaults__'] ) ) {
			return $this->resolved['__defaults__'];
		}

		$defaults = $this->defaults;

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter default settings.
			 *
			 * Allows add-on plugins to register additional setting modules
			 * and keys so they are recognised throughout the settings lifecycle.
			 *
			 * @since 1.0.0
			 *
			 * @param array<string, array<string, mixed>> $defaults Default settings.
			 */
			$defaults = apply_filters( "{$this->slug}_settings_defaults", $defaults );
		}

		$this->resolved['__defaults__'] = $defaults;

		return $defaults;
	}

	/**
	 * Get the default settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_settings( ?string $module = null ): array {
		$defaults = $this->defaults();

		if ( $module ) {
			return isset( $defaults[ $module ] ) ? array( $module => $defaults[ $module ] ) : array();
		}

		// Inject the host module for full retrievals.
		$defaults['host'] = array( 'domain' => $this->domain );

		return $defaults;
	}

	/**
	 * Get settings from wp-config.php constants.
	 *
	 * Builds constant names from the prefix, module key, and setting key
	 * (e.g. prefix `MC` + module `storage` + key `host` → `MC_STORAGE_HOST`).
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings_from_constants( ?string $module = null ): array {
		if ( '' === $this->constant_prefix ) {
			return array();
		}

		$defaults = $this->defaults();
		$result   = array();

		$modules_to_check = $module ? array( $module => $defaults[ $module ] ?? array() ) : $defaults;

		foreach ( $modules_to_check as $module_key => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				$constant = strtoupper( "{$this->constant_prefix}_{$module_key}_{$key}" );

				if ( defined( $constant ) ) {
					$result[ $module_key ][ $key ] = constant( $constant );
				} elseif ( strpos( $key, 'enc_' ) === 0 ) {
					// For encrypted fields, also check without the enc_ prefix.
					$enc_constant = str_replace( 'ENC_', '', $constant );
					if ( defined( $enc_constant ) ) {
						$result[ $module_key ][ $key ] = constant( $enc_constant );
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get settings from the database.
	 *
	 * Returns an empty array in standalone mode or when WordPress is
	 * not loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings_from_db( ?string $module = null ): array {
		if ( $this->standalone || ! function_exists( 'get_option' ) ) {
			return array();
		}

		$db_settings = (array) get_option( $this->option_name, array() );

		if ( $module ) {
			$module_settings = isset( $db_settings[ $module ] ) ? (array) $db_settings[ $module ] : array();
			return isset( $db_settings[ $module ] ) ? array( $module => $module_settings ) : array();
		}

		$result = array_map(
			function ( $setting ): array {
				return (array) $setting;
			},
			$db_settings
		);

		return $result;
	}

	/**
	 * Get the source of a setting value.
	 *
	 * Checks sources in priority order and returns the first match.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module The settings module.
	 * @param string $key    The setting key.
	 *
	 * @return string 'constant', 'file', 'db', or 'default'.
	 */
	public function get_source( string $module, string $key ): string {
		$constant_settings = $this->get_settings_from_constants( $module );
		if ( isset( $constant_settings[ $module ][ $key ] ) ) {
			return 'constant';
		}

		if ( $this->config_file ) {
			$file_settings = $this->config_file->read( $module );
			if ( isset( $file_settings[ $module ][ $key ] ) ) {
				return 'file';
			}
		}

		$db_settings = $this->get_settings_from_db( $module );
		if ( isset( $db_settings[ $module ][ $key ] ) ) {
			return 'db';
		}

		return 'default';
	}

	// ─── Filter settings by constants ───────────────────────────────────

	/**
	 * Filter settings: strip constant-defined keys and merge with defaults.
	 *
	 * Hooked into `option_{$name}` and `default_option_{$name}` to ensure
	 * the returned value is always a clean, schema-conformant array.
	 *
	 * @since 1.0.0
	 *
	 * @param false|array<string, array<string, mixed>> $settings The option value.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function filter_settings_by_constants( $settings ): array {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		// Remove constant-defined keys from the stored value.
		$constant_settings = $this->get_settings_from_constants();
		foreach ( $constant_settings as $mod => $mod_settings ) {
			foreach ( $mod_settings as $key => $value ) {
				unset( $settings[ $mod ][ $key ] );
			}
		}

		// Merge with defaults: add missing keys, remove obsolete ones.
		$default_settings = $this->defaults();
		foreach ( $default_settings as $mod => $mod_settings ) {
			if ( ! is_array( $mod_settings ) ) {
				continue;
			}

			if ( ! isset( $settings[ $mod ] ) ) {
				$settings[ $mod ] = array();
			}

			// Add missing default keys.
			foreach ( $mod_settings as $key => $value ) {
				if ( ! isset( $settings[ $mod ][ $key ] ) && ! isset( $constant_settings[ $mod ][ $key ] ) ) {
					$settings[ $mod ][ $key ] = $value;
				}
			}

			// Remove obsolete keys.
			foreach ( $settings[ $mod ] as $key => $value ) {
				if ( ! array_key_exists( $key, $mod_settings ) ) {
					unset( $settings[ $mod ][ $key ] );
				}
			}
		}

		// Remove obsolete modules.
		foreach ( $settings as $mod => $mod_settings ) {
			if ( ! isset( $default_settings[ $mod ] ) && 'host' !== $mod ) {
				unset( $settings[ $mod ] );
			}
		}

		return $settings;
	}

	// ─── Encryption ─────────────────────────────────────────────────────

	/**
	 * Encrypt sensitive settings data (fields prefixed with 'enc_').
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $settings The settings before saving.
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws \Exception        If random bytes cannot be generated.
	 * @throws \SodiumException  If encryption fails.
	 */
	public function encrypt_sensitive_settings_data( array $settings ): array {
		foreach ( $settings as $module => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( strpos( $key, 'enc_' ) === 0 && is_string( $value ) ) {
					$settings[ $module ][ $key ] = self::encrypt_value( $value );
				}
			}
		}

		return $settings;
	}

	/**
	 * Decrypt sensitive settings data (fields prefixed with 'enc_').
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $settings The stored settings.
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws \SodiumException If decryption fails.
	 */
	public function decrypt_sensitive_settings_data( array $settings ): array {
		foreach ( $settings as $module => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( strpos( $key, 'enc_' ) === 0 && is_string( $value ) ) {
					$settings[ $module ][ $key ] = self::decrypt_value( $value );
				}
			}
		}

		return $settings;
	}

	/**
	 * Encrypt a value using sodium.
	 *
	 * Uses `AUTH_KEY` and `SECURE_AUTH_KEY` as the key material. Values
	 * already prefixed with `ENC:` are returned as-is.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The value to encrypt.
	 *
	 * @return string The encrypted value prefixed with 'ENC:'.
	 *
	 * @throws \Exception       If random bytes cannot be generated.
	 * @throws \SodiumException If encryption fails.
	 */
	public static function encrypt_value( string $value ): string {
		if ( empty( $value ) || strpos( $value, 'ENC:' ) === 0 ) {
			return $value;
		}

		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$key   = sodium_crypto_generichash( AUTH_KEY . SECURE_AUTH_KEY, '', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES ); // @phpstan-ignore-line

		$encrypted = sodium_crypto_secretbox( $value, $nonce, $key );
		return 'ENC:' . base64_encode( $nonce . $encrypted );
	}

	/**
	 * Decrypt a value using sodium.
	 *
	 * Loads WordPress's bundled sodium_compat if the native extension
	 * is not available.
	 *
	 * @since 1.0.0
	 *
	 * @param string $encrypted_value The encrypted value.
	 *
	 * @return string The decrypted value, or empty string on failure.
	 *
	 * @throws \SodiumException If decryption fails.
	 */
	public static function decrypt_value( string $encrypted_value ): string {
		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			if ( defined( 'ABSPATH' ) ) {
				require_once ABSPATH . 'wp-includes/sodium_compat/autoload.php';
			}
		}

		if ( strpos( $encrypted_value, 'ENC:' ) !== 0 ) {
			return $encrypted_value;
		}

		$encrypted_value = substr( $encrypted_value, 4 );
		$key             = sodium_crypto_generichash( AUTH_KEY . SECURE_AUTH_KEY, '', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES ); // @phpstan-ignore-line
		$decoded         = base64_decode( $encrypted_value );

		$nonce      = mb_substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

		$decrypted = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

		return false !== $decrypted ? $decrypted : '';
	}

	// ─── Backup / Restore ───────────────────────────────────────────────

	/**
	 * Back up current settings to a transient.
	 *
	 * The backup expires after 12 hours.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to back up, or null for all.
	 *
	 * @return void
	 */
	public function backup( ?string $module = null ): void {
		$current = $this->resolve( $module );

		if ( $current ) {
			set_transient( $this->option_name . '_backup', $current, 12 * HOUR_IN_SECONDS );
		}
	}

	/**
	 * Check if a backup exists.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_backup(): bool {
		return (bool) get_transient( $this->option_name . '_backup' );
	}

	/**
	 * Restore settings from a backup transient.
	 *
	 * Deletes the transient after a successful restore.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if restored successfully.
	 */
	public function restore_backup(): bool {
		$backup = get_transient( $this->option_name . '_backup' );

		if ( ! $backup ) {
			return false;
		}

		update_option( $this->option_name, $backup );
		delete_transient( $this->option_name . '_backup' );

		return true;
	}

	/**
	 * Check if settings are at their defaults (ignoring constants).
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_default_settings(): bool {
		return $this->resolve( null, true ) === $this->get_default_settings();
	}

	// ─── Reset ──────────────────────────────────────────────────────────

	/**
	 * Reset settings to defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module The module to reset, or null for all.
	 *
	 * @return bool True if reset successfully.
	 */
	public function reset( ?string $module = null ): bool {
		$this->resolved = array();

		if ( null === $module ) {
			return update_option( $this->option_name, $this->defaults() );
		}

		$settings = $this->resolve( null, true );
		$defaults = $this->get_default_settings( $module );
		if ( isset( $defaults[ $module ] ) ) {
			$settings[ $module ] = $defaults[ $module ];
		}

		return update_option( $this->option_name, $settings );
	}

	// ─── Import / Export ────────────────────────────────────────────────

	/**
	 * Export settings.
	 *
	 * Encrypted fields are either decrypted or stripped depending on
	 * the `$include_encrypted` flag. The `host` module is always excluded.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module            Module to export, or null for all.
	 * @param bool        $include_encrypted Whether to include decrypted values.
	 *
	 * @return array<string, mixed>
	 */
	public function export( ?string $module = null, bool $include_encrypted = false ): array {
		$settings = $this->resolve( $module, true );

		foreach ( $settings as $module_key => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( strpos( $key, 'enc_' ) !== 0 ) {
					continue;
				}

				if ( $include_encrypted && is_string( $value ) ) {
					$settings[ $module_key ][ $key ] = self::decrypt_value( $value );
				} elseif ( ! $include_encrypted ) {
					unset( $settings[ $module_key ][ $key ] );
				}
			}
		}

		unset( $settings['host'] );

		return $settings;
	}

	/**
	 * Import settings from an array.
	 *
	 * Only modules present in the defaults are accepted; unknown modules
	 * are silently discarded.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings The settings to import.
	 * @param bool                 $merge    Whether to merge with existing.
	 *
	 * @return bool True if imported successfully.
	 */
	public function import( array $settings, bool $merge = true ): bool {
		$valid_modules     = array_keys( $this->defaults() );
		$filtered_settings = array();

		foreach ( $settings as $module => $module_settings ) {
			if ( in_array( $module, $valid_modules, true ) && is_array( $module_settings ) ) {
				$filtered_settings[ $module ] = $module_settings;
			}
		}

		if ( empty( $filtered_settings ) ) {
			return false;
		}

		if ( $merge ) {
			$current = $this->resolve( null, true );
			foreach ( $filtered_settings as $module => $module_settings ) {
				if ( ! isset( $current[ $module ] ) ) {
					$current[ $module ] = array();
				}
				$current[ $module ] = array_merge( $current[ $module ], $module_settings );
			}
			$filtered_settings = $current;
		}

		$this->resolved = array();

		return update_option( $this->option_name, $filtered_settings );
	}

	// ─── Utilities ──────────────────────────────────────────────────────

	/**
	 * Coerce a string value to its appropriate PHP type.
	 *
	 * Converts `'true'`/`'false'`/`'null'` to their native types, and
	 * numeric strings to int or float.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The string value.
	 *
	 * @return mixed The coerced value.
	 */
	public static function coerce_value( string $value ) {
		$lower = strtolower( $value );

		if ( 'true' === $lower ) {
			return true;
		}
		if ( 'false' === $lower ) {
			return false;
		}
		if ( 'null' === $lower ) {
			return null;
		}
		if ( is_numeric( $value ) && strpos( $value, '.' ) === false ) {
			return (int) $value;
		}
		if ( is_numeric( $value ) && strpos( $value, '.' ) !== false ) {
			return (float) $value;
		}

		return $value;
	}

	/**
	 * Get the option name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return $this->option_name;
	}

	// ─── Config file hook callbacks ─────────────────────────────────────

	/**
	 * Handle add_option hook — sync newly created options to the config file.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $option   The option name.
	 * @param array<mixed> $settings The settings value.
	 *
	 * @return void
	 */
	public function on_add_option( string $option, array $settings ): void {
		if ( $this->config_file ) {
			$this->config_file->write( $settings );
		}
	}

	/**
	 * Handle update_option hook — sync updated options to the config file.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $old_settings The old value.
	 * @param array<mixed> $settings     The new value.
	 *
	 * @return void
	 */
	public function on_update_option( array $old_settings, array $settings ): void {
		if ( $this->config_file ) {
			$this->config_file->write( $settings );
		}
	}

	/**
	 * Handle the delete_option hook — remove the config file when the option is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option The option name.
	 *
	 * @return void
	 */
	public function on_delete_option( string $option ): void {
		if ( $option !== $this->option_name || ! $this->config_file ) {
			return;
		}

		$this->config_file->delete();
	}

	// ─── Private helpers ────────────────────────────────────────────────

	/**
	 * Resolve the domain identifier for config file naming.
	 *
	 * Falls back to `site_url()` when `$_SERVER['HTTP_HOST']` is unavailable
	 * (e.g. WP-CLI context).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function resolve_domain(): string {
		$host = '';

		if ( ServerVars::has( 'HTTP_HOST' ) ) {
			$host = ServerVars::get( 'HTTP_HOST' );
		} elseif ( function_exists( 'site_url' ) ) {
			$parsed = wp_parse_url( site_url() );
			$host   = $parsed['host'] ?? '';
		}

		return (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $host );
	}
}
