<?php
/**
 * Abilities API controller — registers categories and abilities for the host plugin.
 *
 * @package MilliBase
 * @author  Philipp Wellmer & Vedran <hello@millipress.com>
 */

namespace MilliBase\Abilities;

use MilliBase\Concerns\HasConfig;
use MilliBase\Settings;

/**
 * Registers the plugin's ability category and abilities with the
 * WordPress Abilities API. See docs/02-usage/05-abilities.md for the
 * config-array schema and the lazy-init / soft-detect semantics.
 *
 * @since 2.5.0
 */
final class Controller {

	use HasConfig;

	/**
	 * The plugin configuration array.
	 *
	 * @since 2.5.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The Settings backing this controller.
	 * Cross-prefix tolerant; do not add a native type.
	 * See docs/04-reference/04-namespace-prefixing.md.
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 *
	 * @since 2.5.0
	 * @var Settings
	 */
	private $settings;

	/**
	 * Create a new abilities Controller instance.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $config   The plugin configuration.
	 * @param Settings             $settings Cross-prefix tolerant; see {@see self::$settings}.
	 */
	public function __construct( array $config, $settings ) {
		$this->config   = $config;
		$this->settings = $settings;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the plugin's ability category on `wp_abilities_api_categories_init`.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		$slug = $this->config_string( 'slug' );
		if ( ! self::is_valid_slug( $slug ) ) {
			return;
		}

		// Idempotent — avoids core's duplicate-registration warning.
		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( $slug ) ) {
			return;
		}

		$abilities = is_array( $this->config['abilities'] ?? null ) ? $this->config['abilities'] : array();
		$category  = is_array( $abilities['category'] ?? null ) ? $abilities['category'] : array();

		$override_label = is_string( $category['label'] ?? null ) ? $category['label'] : '';
		$label          = '' !== $override_label
			? $override_label
			: $this->config_string( 'menu_title', $slug );

		$override_description = is_string( $category['description'] ?? null ) ? $category['description'] : '';
		$description          = '' !== $override_description
			? $override_description
			: sprintf(
				/* translators: %s: the human-readable plugin name. */
				__( 'Operations exposed by %s.', 'millibase' ),
				$label
			);

		wp_register_ability_category(
			$slug,
			array(
				'label'       => $label,
				'description' => $description,
			)
		);
	}

	/**
	 * Register the plugin's abilities on `wp_abilities_api_init`.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$slug = $this->config_string( 'slug' );
		if ( ! self::is_valid_slug( $slug ) ) {
			return;
		}

		$config  = is_array( $this->config['abilities'] ?? null ) ? $this->config['abilities'] : array();
		$entries = is_array( $config['extend'] ?? null ) ? array_values( $config['extend'] ) : array();

		// Append, not prepend — host entries register first, framework duplicates skip via wp_has_ability().
		if ( self::should_expose_preset( $config['expose'] ?? false, 'settings' ) ) {
			$entries = array_merge( $entries, FrameworkAbilities::settings( $this->settings ) );
		}

		if ( array() === $entries ) {
			return;
		}

		$default_capability = $this->config_string( 'capability', 'manage_options' );

		foreach ( $entries as $ability ) {
			if ( ! is_array( $ability ) ) {
				continue;
			}

			$id          = is_string( $ability['id'] ?? null ) ? $ability['id'] : '';
			$label       = is_string( $ability['label'] ?? null ) ? $ability['label'] : '';
			$description = is_string( $ability['description'] ?? null ) ? $ability['description'] : '';

			if (
				'' === $id
				|| '' === $label
				|| '' === $description
				|| ! is_callable( $ability['callback'] ?? null )
			) {
				continue;
			}

			// Bare ids only — foreign namespaces would let this Manager shadow
			// another plugin that legitimately owns the prefix, depending on load order.
			if ( strpos( $id, '/' ) !== false ) {
				if ( function_exists( '_doing_it_wrong' ) ) {
					_doing_it_wrong(
						__METHOD__,
						esc_html(
							sprintf(
								/* translators: 1: ability id as supplied; 2: host plugin slug. */
								__( 'Ability id "%1$s" contains a forward slash. Use bare ids; MilliBase auto-prefixes them with "%2$s/".', 'millibase' ),
								$id,
								$slug
							)
						),
						'2.5.0'
					);
				}
				continue;
			}

			$name = "{$slug}/{$id}";
			if ( ! self::is_valid_name( $name ) ) {
				continue;
			}

			// Idempotent — avoids core's duplicate-registration warning.
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $name ) ) {
				continue;
			}

			$args = array(
				'label'               => $label,
				'description'         => $description,
				'category'            => $slug,
				'execute_callback'    => self::wrap_callback( $ability['callback'], $name ),
				'permission_callback' => $this->build_permission_callback( $ability, $default_capability, $name ),
			);

			if ( is_array( $ability['input_schema'] ?? null ) && array() !== $ability['input_schema'] ) {
				$args['input_schema'] = $ability['input_schema'];
			} else {
				$args['input_schema'] = array(
					'type'                 => 'object',
					'additionalProperties' => false,
				);
			}
			if ( is_array( $ability['output_schema'] ?? null ) && array() !== $ability['output_schema'] ) {
				$args['output_schema'] = $ability['output_schema'];
			}
			if ( is_array( $ability['meta'] ?? null ) && array() !== $ability['meta'] ) {
				$args['meta'] = $ability['meta'];
			}

			wp_register_ability( $name, $args );
		}
	}

	/**
	 * Whether the given framework preset should be exposed.
	 *
	 * `true` exposes every built-in preset (including ones added in future
	 * MilliBase releases). An array of names exposes only those listed. Any
	 * other value (false, null, omitted) exposes nothing.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param mixed  $expose The `abilities.expose` config value.
	 * @param string $preset The preset name to test for.
	 * @return bool
	 */
	private static function should_expose_preset( $expose, string $preset ): bool {
		if ( true === $expose ) {
			return true;
		}
		return is_array( $expose ) && in_array( $preset, $expose, true );
	}

	/**
	 * Wrap a user callback with a `Throwable` catch so an uncaught
	 * exception cannot leak a stack trace through the abilities REST
	 * surface (which on Acorn-based stacks renders Ignition-style HTML).
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param callable $callback The host plugin's callback.
	 * @param string   $name     The fully qualified ability name, used for log context.
	 * @return callable
	 */
	private static function wrap_callback( $callback, string $name ): callable {
		return static function ( $input = null ) use ( $callback, $name ) {
			try {
				return $callback( $input );
			} catch ( \Throwable $e ) {
				// Strip newlines so an attacker-controlled exception message can't inject fake log lines.
				$safe_message = str_replace( array( "\n", "\r" ), ' | ', $e->getMessage() );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Server-side log only; user-facing response is sanitised below.
				error_log( sprintf( '[MilliBase] Ability %s callback threw: %s in %s:%d', $name, $safe_message, $e->getFile(), $e->getLine() ) );
				return new \WP_Error(
					'ability_callback_exception',
					__( 'The ability callback threw an exception.', 'millibase' ),
					array( 'status' => 500 )
				);
			}
		};
	}

	/**
	 * Validate a plugin slug against the abilities-api category-slug regex.
	 *
	 * @since 2.5.0
	 *
	 * @param string $slug The plugin slug to validate.
	 * @return bool
	 *
	 * @phpstan-assert-if-true non-falsy-string&lowercase-string $slug
	 */
	private static function is_valid_slug( string $slug ): bool {
		// The regex below matches '0' as a 1-char slug. Exclude it explicitly
		// so the @phpstan-assert non-falsy guarantee holds; no realistic slug
		// is literally '0' anyway.
		if ( '0' === $slug ) {
			return false;
		}
		return 1 === preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
	}

	/**
	 * Validate a fully qualified ability name (`<namespace>/<id>`) against core's two-segment regex.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name The fully qualified ability name to validate.
	 * @return bool
	 *
	 * @phpstan-assert-if-true non-falsy-string&lowercase-string $name
	 */
	private static function is_valid_name( string $name ): bool {
		// Any string matching this regex contains a literal `/` and thus has
		// length ≥ 3 with at least one non-`[0-9]` char, so it's automatically
		// non-falsy and lowercase — unlike is_valid_slug which needs to
		// special-case the standalone '0'.
		return 1 === preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*\/[a-z0-9]+(?:-[a-z0-9]+)*$/', $name );
	}

	/**
	 * Resolve a permission callback: explicit callable → `capability` string → plugin default.
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $ability            The ability config entry.
	 * @param string               $default_capability The plugin-default capability.
	 * @param string               $name               The fully qualified ability name, used for log context.
	 * @return callable
	 */
	private function build_permission_callback( array $ability, string $default_capability, string $name ): callable {
		if ( is_callable( $ability['permission_callback'] ?? null ) ) {
			// Wrap host-supplied permission callbacks for the same reason as execute_callback:
			// a thrown exception would otherwise leak a stack trace through the REST surface.
			return self::wrap_callback( $ability['permission_callback'], $name );
		}

		$capability = is_string( $ability['capability'] ?? null ) ? $ability['capability'] : $default_capability;

		return static function () use ( $capability ): bool {
			return current_user_can( $capability );
		};
	}
}
