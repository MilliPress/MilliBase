<?php
/**
 * Settings manager — the main entry point for consuming plugins.
 *
 * Usage:
 *   $manager = new \MilliBase\Manager([
 *       'slug' => 'milliplugin',
 *       'tabs' => [ ... ],
 *       // ... full config array
 *       // option_name defaults to {slug} ('milliplugin')
 *   ]);
 *
 *   // Programmatic access:
 *   $manager->settings()->get('cache.ttl');
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Orchestrator that wires Settings + Schema + AdminPage + RestController together.
 *
 * The constructor takes the full configuration array, creates all internal
 * components, and registers all WordPress hooks directly.
 *
 * @since 1.0.0
 */
final class Manager {

	/**
	 * The Settings instance.
	 *
	 * @since 1.0.0
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * The Schema instance.
	 *
	 * @since 1.0.0
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * The AdminPage instance.
	 *
	 * @since 1.0.0
	 * @var AdminPage|null
	 */
	private ?AdminPage $admin_page = null;

	/**
	 * The RestController instance.
	 *
	 * @since 1.0.0
	 * @var RestController|null
	 */
	private ?RestController $rest_controller = null;

	/**
	 * The full configuration array.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Create a new Manager instance and wire all components.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The full settings configuration array.
	 */
	public function __construct( array $config ) {
		$slug = is_string( $config['slug'] ?? null ) ? $config['slug'] : 'millibase';

		// Auto-derive defaults from slug.
		if ( ! isset( $config['option_name'] ) ) {
			$config['option_name'] = $slug;
		}
		if ( ! isset( $config['rest_namespace'] ) ) {
			$config['rest_namespace'] = $slug . '/v1';
		}

		$this->config   = $config;
		$this->schema   = $this->resolve_schema();
		$this->settings = $this->resolve_settings();

		$this->boot();
	}

	// ─── Boot ───────────────────────────────────────────────────────────

	/**
	 * Register all WordPress integrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function boot(): void {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		if ( did_action( 'init' ) ) {
			$this->register_settings();
		} else {
			add_action( 'init', array( $this, 'register_settings' ) );
		}

		$this->admin_page = new AdminPage( $this->config, $this->schema );
		$this->admin_page->register_hooks();

		$this->rest_controller = new RestController( $this->config, $this->settings );
		$this->rest_controller->register_hooks();
	}

	/**
	 * Register the option with WordPress for the REST API.
	 *
	 * Uses the Settings' full defaults (including non-UI fields) so the REST
	 * schema covers every setting key, not just those with UI fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$option_name = $this->config_string( 'option_name' );
		$defaults    = $this->settings->get_default_settings();

		register_setting(
			'options',
			$option_name,
			array(
				'type'         => 'object',
				'default'      => $defaults,
				'show_in_rest' => array(
					'schema' => $this->schema->get_rest_schema( $defaults ),
				),
			)
		);
	}

	// ─── Accessors ──────────────────────────────────────────────────────

	/**
	 * Get the Settings instance for programmatic settings access.
	 *
	 * @since 1.0.0
	 *
	 * @return Settings
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * Get the Schema instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Schema
	 */
	public function schema(): Schema {
		return $this->schema;
	}

	// ─── Helpers ────────────────────────────────────────────────────────

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

	// ─── Private resolvers ──────────────────────────────────────────────

	/**
	 * Create and optionally filter the Schema from the configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return Schema
	 */
	private function resolve_schema(): Schema {
		$slug = $this->config_string( 'slug', 'millibase' );

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the settings configuration before Schema initialization.
			 *
			 * @param array<string, mixed> $config The full settings configuration array.
			 */
			$this->config = apply_filters( "{$slug}_settings_schema", $this->config );
		}

		return new Schema( $this->config );
	}

	/**
	 * Resolve the Settings: use an external instance or build one from the schema.
	 *
	 * Pass a pre-built Settings via `$config['settings']` when you need custom
	 * encryption, constants, or config-file support. Otherwise the manager
	 * creates its own Settings from schema-extracted and explicit defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return Settings
	 */
	private function resolve_settings(): Settings {
		if ( isset( $this->config['settings'] ) && $this->config['settings'] instanceof Settings ) {
			$settings = $this->config['settings'];
		} else {
			// Merge explicit defaults (non-UI fields) with schema-extracted defaults.
			$defaults = array_replace_recursive(
				(array) ( $this->config['defaults'] ?? array() ),
				$this->schema->get_defaults()
			);

			$settings = new Settings(
				array(
					'slug'            => $this->config['slug'] ?? '',
					'option_name'     => $this->config['option_name'],
					'constant_prefix' => $this->config['constant_prefix'] ?? '',
					'encryption'      => $this->config['encryption'] ?? false,
					'defaults'        => $defaults,
					'config_file'     => $this->config['config_file'] ?? false,
				)
			);
		}

		// Always merge schema defaults so active-toggle keys (and any other
		// schema-derived defaults) are recognised even by pre-built instances.
		$settings->merge_defaults( $this->schema->get_defaults() );

		return $settings;
	}
}
