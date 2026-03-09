<?php
/**
 * Handles admin menu registration and asset enqueuing.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Registers the admin menu page and enqueues the pre-built React JS bundle.
 *
 * @since 1.0.0
 */
final class AdminPage {

	/**
	 * The full settings configuration array.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The Schema instance.
	 *
	 * @since 1.0.0
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * Create a new AdminPage instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The settings configuration.
	 * @param Schema               $schema The schema instance.
	 */
	public function __construct( array $config, Schema $schema ) {
		$this->config = $config;
		$this->schema = $schema;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
	}

	/**
	 * Add the admin menu item.
	 *
	 * Registers either a top-level menu page or a submenu page depending
	 * on whether `menu_parent` is set in the configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		$slug        = $this->config_string( 'slug', 'millibase' );
		$page_title  = $this->config_string( 'page_title', 'Settings' );
		$menu_title  = $this->config_string( 'menu_title', 'Settings' );
		$capability  = $this->config_string( 'capability', 'manage_options' );
		$menu_parent = $this->config_string( 'menu_parent', 'options-general.php' );
		$menu_icon   = $this->config_string( 'menu_icon' );

		$render_callback = function () use ( $slug ) {
			printf( '<div class="wrap millibase-page" id="%s-settings" data-slug="%s"></div>', esc_attr( $slug ), esc_attr( $slug ) );
		};

		if ( $menu_parent ) {
			add_submenu_page(
				$menu_parent,
				$page_title,
				$menu_title,
				$capability,
				$slug,
				$render_callback
			);
		} else {
			add_menu_page(
				$page_title,
				$menu_title,
				$capability,
				$slug,
				$render_callback,
				$menu_icon
			);
		}
	}

	/**
	 * Enqueue the pre-built JS bundle and CSS on the settings page.
	 *
	 * Only loads assets when the current screen matches the registered
	 * settings page hook suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $admin_page The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_settings_assets( string $admin_page ): void {
		$slug        = $this->config_string( 'slug', 'millibase' );
		$menu_parent = $this->config_string( 'menu_parent', 'options-general.php' );

		// Determine the expected hook suffix.
		$expected_suffix = $menu_parent
			? 'settings_page_' . $slug
			: 'toplevel_page_' . $slug;

		if ( $admin_page !== $expected_suffix ) {
			return;
		}

		$this->enqueue_bundle();
		$this->inject_config();

		// WordPress components styles.
		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Enqueue the pre-built millibase bundle.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function enqueue_bundle(): void {
		$package_dir = $this->resolve_package_dir();
		$asset_file  = $package_dir . '/build/millibase.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset     = include $asset_file;
		$build_url = $this->resolve_build_url();

		wp_enqueue_style(
			'millibase',
			$build_url . '/millibase.css',
			array(),
			$asset['version']
		);

		wp_enqueue_script(
			'millibase',
			$build_url . '/millibase.js',
			array_merge( $asset['dependencies'], array( 'wp-api-fetch' ) ),
			$asset['version'],
			array( 'in_footer' => true )
		);
	}

	/**
	 * Inject the settings schema configuration via inline script.
	 *
	 * Passes the client-safe schema, actions, and header config to the
	 * React UI through `window.MilliBase.init()`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function inject_config(): void {
		$slug           = $this->config_string( 'slug', 'millibase' );
		$option_name    = $this->config_string( 'option_name', 'millibase' );
		$rest_namespace = $this->config_string( 'rest_namespace', 'millibase/v1' );

		// Build the client-safe actions list.
		$client_actions = array();
		$actions        = is_array( $this->config['actions'] ?? null ) ? $this->config['actions'] : array();
		foreach ( $actions as $action ) {
			$names    = (array) ( $action['name'] ?? '' );
			$endpoint = $action['endpoint'] ?? '';
			$method   = $action['method'] ?? 'POST';

			foreach ( $names as $name ) {
				$client_actions[] = array(
					'name'     => $name,
					'endpoint' => $endpoint,
					'method'   => $method,
				);
			}
		}

		$config_json = wp_json_encode(
			array(
				'slug'            => $slug,
				'optionName'      => $option_name,
				'restNamespace'   => $rest_namespace,
				'containerId'     => $slug . '-settings',
				'schema'          => $this->schema->to_client_array(),
				'header'          => $this->config['header'] ?? array(),
				'troubleshooting' => $this->config['troubleshooting'] ?? null,
				'actions'         => $client_actions,
			)
		);

		$escaped_slug = esc_js( $slug );

		wp_add_inline_script(
			'millibase',
			"window.MilliBase = window.MilliBase || {}; window.MilliBase.init = window.MilliBase.init || function(s,c){ window.MilliBase.configs = window.MilliBase.configs || {}; window.MilliBase.configs[s] = c; }; window.MilliBase.init('{$escaped_slug}', {$config_json});",
			'before'
		);
	}

	/**
	 * Resolve the package directory (where build/ lives).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function resolve_package_dir(): string {
		// The package's src/ is one level up from this file.
		return dirname( __DIR__ );
	}

	/**
	 * Resolve the URL to the package's build/ directory.
	 *
	 * Resolution order:
	 * 1. Explicit `build_url` config override.
	 * 2. Plugin basename from `{SLUG}_BASENAME` constant (handles symlinks).
	 * 3. Direct `plugins_url()` from the package directory.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function resolve_build_url(): string {
		if ( ! empty( $this->config['build_url'] ) ) {
			return $this->config_string( 'build_url' );
		}

		$package_dir = $this->resolve_package_dir();
		$slug        = $this->config_string( 'slug', 'millibase' );
		$constant    = strtoupper( str_replace( '-', '_', $slug ) ) . '_BASENAME';

		if ( defined( $constant ) ) {
			$basename   = constant( $constant );
			$plugin_dir = plugin_dir_path( WP_PLUGIN_DIR . '/' . $basename );
			$relative   = str_replace( $plugin_dir, '', $package_dir . '/' );

			// If the package resolves outside the plugin directory (e.g. Composer
			// path repository symlink), try the vendor path instead.
			if ( strpos( $relative, '/' ) === 0 || strpos( $relative, DIRECTORY_SEPARATOR ) === 0 ) {
				$relative = $this->resolve_vendor_relative_path( $plugin_dir );
			}

			if ( $relative ) {
				return plugins_url( $relative . 'build', WP_PLUGIN_DIR . '/' . $basename );
			}
		}

		return plugins_url( 'build', $package_dir . '/millibase' );
	}

	/**
	 * Find the package path relative to the plugin via vendor directory.
	 *
	 * When the package is installed as a Composer path repository (symlink),
	 * __DIR__ resolves to the real path outside the plugin. This method
	 * walks common vendor paths to find a working relative path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_dir The plugin's absolute directory path.
	 *
	 * @return string The relative path (with trailing slash) or empty string.
	 */
	private function resolve_vendor_relative_path( string $plugin_dir ): string {
		$candidates = array(
			'vendor/millipress/millibase/',
			'deps/millipress/millibase/',
		);

		foreach ( $candidates as $candidate ) {
			$candidate_build = $plugin_dir . $candidate . 'build/millibase.asset.php';
			if ( file_exists( $candidate_build ) ) {
				return $candidate;
			}
		}

		return '';
	}

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
}
