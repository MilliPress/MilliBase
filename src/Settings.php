<?php
/**
 * Settings facade — the main entry point for consuming plugins.
 *
 * Usage:
 *   $settings = new \MilliBase\Settings([
 *       'option_name' => 'milliplugin',
 *       'slug'        => 'milliplugin',
 *       'tabs'        => [ ... ],
 *       // ... full config array
 *   ]);
 *
 *   // Programmatic access:
 *   $settings->store()->get('cache.ttl');
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Facade that wires Store + Schema + AdminPage + RestController together.
 *
 * The constructor takes the full configuration array, creates all internal
 * components, and registers all WordPress hooks directly.
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * The Store instance.
	 *
	 * @since 1.0.0
	 * @var Store
	 */
	private Store $store;

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
	 * Create a new Settings instance and wire all components.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The full settings configuration array.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
		$this->schema = $this->resolve_schema();
		$this->store  = $this->resolve_store();

		if ( function_exists( 'add_action' ) ) {
			$this->boot();
		}
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
		// Only register Store hooks when the facade created the Store itself.
		// When a Store is provided externally, the caller manages its hooks.
		if ( ! isset( $this->config['store'] ) ) {
			$this->store->register_hooks();
		}

		add_action( 'init', array( $this, 'register_settings' ) );

		$this->admin_page = new AdminPage( $this->config, $this->schema );
		$this->admin_page->register_hooks();

		$this->rest_controller = new RestController( $this->config, $this->store );
		$this->rest_controller->register_hooks();
	}

	/**
	 * Register the option with WordPress for the REST API.
	 *
	 * Uses the Store's full defaults (including non-UI fields) so the REST
	 * schema covers every setting key, not just those with UI fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$option_name = $this->config_string( 'option_name', 'millibase' );
		$defaults    = $this->store->get_default_settings();

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
	 * Get the Store instance for programmatic settings access.
	 *
	 * @since 1.0.0
	 *
	 * @return Store
	 */
	public function store(): Store {
		return $this->store;
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
	 * @param string $key     The config key.
	 * @param string $default The default value.
	 *
	 * @return string
	 */
	private function config_string( string $key, string $default = '' ): string {
		$value = $this->config[ $key ] ?? $default;
		return is_string( $value ) ? $value : $default;
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
			$this->config = apply_filters( "{$slug}_schema", $this->config );
		}

		return new Schema( $this->config );
	}

	/**
	 * Resolve the Store: use an external instance or build one from the schema.
	 *
	 * Pass a pre-built Store via `$config['store']` when you need custom
	 * encryption, constants, or config-file support. Otherwise the facade
	 * creates its own Store from schema-extracted and explicit defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return Store
	 */
	private function resolve_store(): Store {
		if ( isset( $this->config['store'] ) && $this->config['store'] instanceof Store ) {
			return $this->config['store'];
		}

		// Merge explicit defaults (non-UI fields) with schema-extracted defaults.
		$defaults = array_replace_recursive(
			(array) ( $this->config['defaults'] ?? array() ),
			$this->schema->get_defaults()
		);

		return new Store(
			array(
				'option_name'     => $this->config['option_name'] ?? 'millibase',
				'constant_prefix' => $this->config['constant_prefix'] ?? '',
				'encryption'      => $this->config['encryption'] ?? false,
				'defaults'        => $defaults,
				'config_file'     => $this->config['config_file'] ?? false,
			)
		);
	}
}
