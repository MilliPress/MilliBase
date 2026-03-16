<?php
/**
 * Settings manager — the main entry point for consuming plugins.
 *
 * Usage:
 *   add_action( 'init', function () {
 *       $manager = new \MilliBase\Manager( [
 *           'slug' => 'milliplugin',
 *           'tabs' => [ ... ],
 *           // ... full config array
 *           // option_name defaults to {slug} ('milliplugin')
 *       ] );
 *   } );
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
 * Accepts the configuration array directly. The consumer is responsible for
 * creating the Manager on `init` (or later) so that translation functions
 * like __() execute after the textdomain has been loaded (WordPress 6.7+).
 *
 * Settings and Schema are available immediately after construction.
 * WordPress hook registration (boot) is deferred to `init` internally.
 *
 * @since 1.0.0
 */
final class Manager {

	/**
	 * The Settings instance.
	 *
	 * @since 1.0.0
	 */
	private Settings $settings;

	/**
	 * The Schema instance.
	 *
	 * @since 1.0.0
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
	 * The resolved configuration array.
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

		if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
			$this->boot();
		} elseif ( function_exists( 'add_action' ) ) {
			add_action( 'init', array( $this, 'boot' ), 0 );
		}
	}

	/**
	 * Register all WordPress integrations.
	 *
	 * Hooked to `init` (priority 0) when the Manager is created before
	 * `init`, or called immediately when created on/after `init`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		$this->register_settings();

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
		$config = $this->config;

		if ( isset( $config['settings'] ) && $config['settings'] instanceof Settings ) {
			$settings = $config['settings'];
		} else {
			// Merge explicit defaults (non-UI fields) with schema-extracted defaults.
			$defaults = array_replace_recursive(
				(array) ( $config['defaults'] ?? array() ),
				$this->schema->get_defaults()
			);

			$settings = new Settings(
				array(
					'slug'            => $config['slug'] ?? '',
					'option_name'     => $config['option_name'],
					'constant_prefix' => $config['constant_prefix'] ?? '',
					'encryption'      => $config['encryption'] ?? false,
					'config_file'     => $config['config_file'] ?? false,
					'defaults'        => $defaults
				)
			);
		}

		// Always merge schema defaults, so active-toggle keys (and any other
		// schema-derived defaults) are recognized even by pre-built instances.
		$settings->merge_defaults( $this->schema->get_defaults() );

		return $settings;
	}
}
