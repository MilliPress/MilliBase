<?php
/**
 * Handles admin menu registration and asset enqueuing.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

use MilliBase\Concerns\HasConfig;

/**
 * Registers the admin menu page and enqueues the pre-built React JS bundle.
 *
 * @since 1.0.0
 */
final class AdminPage {

	use HasConfig;

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
	 * Hook suffix returned by `add_submenu_page` / `add_menu_page`,
	 * captured for accurate asset-enqueue matching.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	private string $hook_suffix = '';

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
		if ( $this->is_network_admin() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_admin_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
	}

	/**
	 * Whether this page is registered under the Network Admin menu.
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	private function is_network_admin(): bool {
		return ( $this->config['network'] ?? false ) === true
			&& function_exists( 'is_multisite' )
			&& is_multisite();
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

		$render = $this->render_callback( $slug );

		if ( $menu_parent ) {
			$this->hook_suffix = (string) add_submenu_page(
				$menu_parent,
				$page_title,
				$menu_title,
				$capability,
				$slug,
				$render
			);
		} else {
			$this->hook_suffix = (string) add_menu_page(
				$page_title,
				$menu_title,
				$capability,
				$slug,
				$render,
				$menu_icon
			);
		}

		$this->register_footer_filters_on_page();
	}

	/**
	 * Add the network admin menu item.
	 *
	 * Registered when `network` is true in the config (multisite only);
	 * defaults to the network Settings menu with `manage_network_options`.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function add_network_admin_menu(): void {
		$slug        = $this->config_string( 'slug', 'millibase' );
		$page_title  = $this->config_string( 'network_page_title', $this->config_string( 'page_title', 'Settings' ) );
		$menu_title  = $this->config_string( 'network_menu_title', $this->config_string( 'menu_title', 'Settings' ) );
		$capability  = $this->config_string( 'network_capability', 'manage_network_options' );
		$menu_parent = $this->config_string( 'network_menu_parent', 'settings.php' );

		$this->hook_suffix = (string) add_submenu_page(
			$menu_parent,
			$page_title,
			$menu_title,
			$capability,
			$slug,
			$this->render_callback( $slug )
		);

		$this->register_footer_filters_on_page();
	}

	/**
	 * Wire admin-footer overrides scoped to this settings page only.
	 *
	 * Hooked via `load-{hook_suffix}` so the filters are registered only when
	 * our page loads — other admin pages keep the stock WP footer untouched.
	 *
	 * @since 2.6.0
	 *
	 * @return void
	 */
	private function register_footer_filters_on_page(): void {
		if ( '' === $this->hook_suffix ) {
			return;
		}
		add_action( "load-{$this->hook_suffix}", array( $this, 'register_footer_filters' ) );
	}

	/**
	 * Register the footer filters. Public so it can be hooked.
	 *
	 * @since 2.6.0
	 *
	 * @return void
	 */
	public function register_footer_filters(): void {
		add_filter( 'admin_footer_text', array( $this, 'filter_admin_footer_text' ) );
		add_filter( 'update_footer', array( $this, 'filter_update_footer' ), 11 );
	}

	/**
	 * Replace the left admin-footer text ("Thank you for creating with WordPress.")
	 * with the consumer's `footer.left` config when provided. Pass-through otherwise.
	 *
	 * @since 2.6.0
	 *
	 * @param string|mixed $text The default footer text.
	 * @return string
	 */
	public function filter_admin_footer_text( $text ): string {
		$footer = $this->config['footer'] ?? null;
		$left   = is_array( $footer ) ? ( $footer['left'] ?? null ) : null;

		$rendered = self::render_footer_slot( $left );
		if ( null !== $rendered ) {
			return $rendered;
		}
		return is_string( $text ) ? $text : '';
	}

	/**
	 * Replace WP's "Version X.Y.Z" with the consumer's `footer.right` slot.
	 *
	 * When `footer.right` renders to a non-empty value it fully replaces the
	 * right slot. Only when it is unset/empty does MilliBase fall back to its
	 * own `MilliBase X.Y.Z` so the framework version stays visible for support.
	 *
	 * @since 2.6.0
	 *
	 * @param string|mixed $text The default footer text (WP version) — discarded.
	 * @return string
	 */
	public function filter_update_footer( $text ): string {
		unset( $text );

		$footer = $this->config['footer'] ?? null;
		$right  = is_array( $footer ) ? ( $footer['right'] ?? null ) : null;

		$rendered = self::render_footer_slot( $right );
		if ( null !== $rendered && '' !== $rendered ) {
			return $rendered;
		}

		return sprintf( 'MilliBase %s', self::millibase_version() );
	}

	/**
	 * Render a footer slot value into HTML.
	 *
	 * Accepts:
	 *   - A string → returned after `wp_kses_post()` so basic markup (links,
	 *     `<strong>`, `<em>`, `<span>`, etc.) survives while script/style/iframe
	 *     and other dangerous tags are stripped. Consumers can format their
	 *     footer text with anchors and inline styling without hand-escaping.
	 *   - `['component' => 'Name']` → returns a placeholder span the JS bundle
	 *     hydrates into a registered `window.MilliBase.customComponents` entry.
	 *     The component name is `esc_attr()`-escaped before it lands in the
	 *     `data-component` attribute.
	 *   - Anything else → null (caller decides the fallback).
	 *
	 * @since 2.6.0
	 *
	 * @param mixed $slot Raw value from `footer.left` / `footer.right` config.
	 * @return string|null
	 */
	private static function render_footer_slot( $slot ): ?string {
		if ( is_string( $slot ) ) {
			return function_exists( 'wp_kses_post' ) ? wp_kses_post( $slot ) : $slot;
		}
		if ( is_array( $slot ) && isset( $slot['component'] ) && is_string( $slot['component'] ) && '' !== $slot['component'] ) {
			return sprintf(
				'<span class="millibase-footer-slot" data-component="%s"></span>',
				esc_attr( $slot['component'] )
			);
		}
		return null;
	}

	/**
	 * Resolve MilliBase's own installed version via Composer runtime.
	 *
	 * Returns `'dev'` when Composer's `InstalledVersions` isn't available
	 * (very old / non-composer environments) or doesn't know the package.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	private static function millibase_version(): string {
		if ( class_exists( '\Composer\InstalledVersions' ) ) {
			$version = \Composer\InstalledVersions::getPrettyVersion( 'millipress/millibase' );
			if ( is_string( $version ) && '' !== $version ) {
				return $version;
			}
		}
		return 'dev';
	}

	/**
	 * Build the React mount-point render callback for a given page slug.
	 *
	 * @since 2.5.0
	 *
	 * @param string $slug Page slug used as DOM id and `data-slug` attribute.
	 * @return \Closure
	 */
	private function render_callback( string $slug ): \Closure {
		return static function () use ( $slug ) {
			printf( '<div class="wrap millibase-page" id="%s-settings" data-slug="%s"></div>', esc_attr( $slug ), esc_attr( $slug ) );
		};
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
		if ( '' === $this->hook_suffix || $admin_page !== $this->hook_suffix ) {
			return;
		}

		$this->enqueue_bundle();
		$this->inject_config();
		$this->preload_rest_requests();

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
		$build_dir   = $package_dir . '/build';
		$asset_file  = $build_dir . '/millibase.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset      = include $asset_file;
		$build_url  = $this->resolve_build_url();
		$js_deps    = array_merge( $asset['dependencies'], array( 'wp-api-fetch' ) );
		$js_in_foot = array( 'in_footer' => true );

		if ( '' !== $build_url ) {
			wp_enqueue_style( 'millibase', $build_url . '/millibase.css', array(), $asset['version'] );
			wp_enqueue_script( 'millibase', $build_url . '/millibase.js', $js_deps, $asset['version'], $js_in_foot );
			return;
		}

		// Fallback: inline the assets when the build directory is not web-accessible.
		$this->enqueue_inline_assets( $build_dir, $asset['version'], $js_deps, $js_in_foot );
	}

	/**
	 * Enqueue build assets inline via wp_add_inline_script/style.
	 *
	 * Used as a fallback when the build directory is outside the web root
	 * (e.g., Composer library in vendor/).
	 *
	 * @since 1.4.0
	 *
	 * @param string                                                             $build_dir The absolute path to the build directory.
	 * @param string                                                             $version   The asset version string.
	 * @param array<int, string>                                                 $js_deps   JavaScript dependency handles.
	 * @param array{strategy?: string, in_footer?: bool, fetchpriority?: string} $js_args Script registration args.
	 *
	 * @return void
	 */
	private function enqueue_inline_assets( string $build_dir, string $version, array $js_deps, array $js_args ): void {
		$js_file  = $build_dir . '/millibase.js';
		$css_file = $build_dir . '/millibase.css';

		if ( file_exists( $css_file ) ) {
			wp_register_style( 'millibase', false, array(), $version );
			wp_enqueue_style( 'millibase' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build asset.
			wp_add_inline_style( 'millibase', (string) file_get_contents( $css_file ) );
		}

		if ( file_exists( $js_file ) ) {
			wp_register_script( 'millibase', false, $js_deps, $version, $js_args );
			wp_enqueue_script( 'millibase' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build asset.
			wp_add_inline_script( 'millibase', (string) file_get_contents( $js_file ), 'before' );
		}
	}

	/**
	 * Inject the settings schema configuration via an inline script.
	 *
	 * Passes the client-safe schema, actions, and header config to the
	 * React UI through `window.MilliBase.init()`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function inject_config(): void {
		$slug        = $this->config_string( 'slug', 'millibase' );
		$option_name = $this->config_string( 'option_name', 'millibase' );
		// Fold the REST Controller's `/network` route prefix into the localized
		// namespace so the React client fetches the right path on each page.
		$route_prefix   = ! empty( $this->config['network'] ) ? '/network' : '';
		$rest_namespace = $this->config_string( 'rest_namespace', 'millibase/v1' ) . $route_prefix;

		// Build the client-safe actions list.
		$client_actions = array();
		$actions        = is_array( $this->config['actions'] ?? null ) ? $this->config['actions'] : array();
		foreach ( $actions as $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}

			/** @var array<string, mixed> $action */
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
				'isNetworkAdmin'  => $this->is_network_admin(),
				'preloadPaths'    => $this->get_preload_paths(),
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
	 * Build the list of REST paths to preload into the page.
	 *
	 * Defaults to the framework's own `settings` + `status` routes (which the
	 * React provider always fetches on mount). A `preload` config of `false`
	 * disables preloading; an array appends extra consumer paths.
	 *
	 * @since 2.6.3
	 *
	 * @return array<int, string> Leading-slash REST paths, deduped.
	 */
	private function get_preload_paths(): array {
		$preload = $this->config['preload'] ?? null;
		if ( false === $preload ) {
			return array();
		}

		// Mirror inject_config()'s namespace: fold the `/network` route prefix
		// in so preloaded paths match what the client actually requests.
		$route_prefix   = ! empty( $this->config['network'] ) ? '/network' : '';
		$rest_namespace = $this->config_string( 'rest_namespace', 'millibase/v1' ) . $route_prefix;

		$paths = array(
			'/' . $rest_namespace . '/settings',
			'/' . $rest_namespace . '/status',
		);

		if ( is_array( $preload ) ) {
			foreach ( $preload as $path ) {
				if ( is_string( $path ) && '' !== $path ) {
					$paths[] = '/' . ltrim( $path, '/' );
				}
			}
		}

		return array_values( array_unique( $paths ) );
	}

	/**
	 * Embed REST responses inline and register apiFetch's preloading middleware.
	 *
	 * Lets the client's first GET to each preloaded path resolve from embedded
	 * data instead of a network round-trip, so the settings UI mounts without
	 * waiting on `/settings`. Attaches to `wp-api-fetch` so it runs before the
	 * bundle's first request.
	 *
	 * @since 2.6.3
	 *
	 * @return void
	 */
	private function preload_rest_requests(): void {
		if ( ! function_exists( 'rest_preload_api_request' ) ) {
			return;
		}

		$paths = $this->get_preload_paths();
		if ( empty( $paths ) ) {
			return;
		}

		$preload_data = array_reduce( $paths, 'rest_preload_api_request', array() );

		wp_add_inline_script(
			'wp-api-fetch',
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
				wp_json_encode( $preload_data )
			),
			'after'
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
		return dirname( __DIR__ );
	}

	/**
	 * Resolve the URL to the package's build/ directory.
	 *
	 * Resolution order:
	 * 1. Explicit `build_url` config override.
	 * 2. Path detection against WP_CONTENT_DIR (covers plugins, mu-plugins, vendor inside content).
	 * 3. Empty string (triggers inline asset fallback for paths outside the web root).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function resolve_build_url(): string {
		if ( ! empty( $this->config['build_url'] ) ) {
			return $this->config_string( 'build_url' );
		}

		$build_dir   = wp_normalize_path( $this->resolve_package_dir() . '/build' );
		$content_dir = wp_normalize_path( (string) WP_CONTENT_DIR );

		if ( strpos( $build_dir, $content_dir . '/' ) === 0 ) {
			return content_url( substr( $build_dir, strlen( $content_dir ) ) );
		}

		return '';
	}
}
