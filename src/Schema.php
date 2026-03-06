<?php
/**
 * Parses a declarative PHP settings array into defaults, JSON schema, and client config.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Handles the declarative schema: extracts defaults from field definitions,
 * generates JSON schema for show_in_rest, and generates client-safe config.
 *
 * @since 1.0.0
 */
final class Schema {

	/**
	 * The full configuration array.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Cached defaults extracted from field definitions.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $defaults = null;

	/**
	 * Create a new Schema instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The full settings configuration array.
	 */
	public function __construct( array $config ) {
		$config['tabs'] = $this->normalize_tabs( is_array( $config['tabs'] ?? null ) ? $config['tabs'] : array() );
		$this->config   = $config;
	}

	/**
	 * Extract default settings from all field definitions in the schema.
	 *
	 * Returns a nested array keyed by module (from dot-notation field keys).
	 * Results are cached after the first call.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_defaults(): array {
		if ( null !== $this->defaults ) {
			return $this->defaults;
		}

		$defaults = array();

		foreach ( $this->get_all_fields() as $field ) {
			if ( ! isset( $field['key'] ) || ! is_string( $field['key'] ) ) {
				continue;
			}

			$parts = explode( '.', $field['key'] );
			if ( count( $parts ) < 2 ) {
				continue;
			}

			$module = $parts[0];
			$key    = $parts[1];

			if ( ! isset( $defaults[ $module ] ) ) {
				$defaults[ $module ] = array();
			}

			$defaults[ $module ][ $key ] = $field['default'] ?? null;
		}

		$this->defaults = $defaults;
		return $defaults;
	}

	/**
	 * Generate a JSON schema for WordPress register_setting() show_in_rest.
	 *
	 * When $defaults is null, uses schema-extracted defaults (UI fields only).
	 * Pass full defaults (including non-UI fields) to generate a complete schema.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>>|null $defaults Optional defaults to generate schema from.
	 *
	 * @return array<string, mixed>
	 */
	public function get_rest_schema( ?array $defaults = null ): array {
		if ( null === $defaults ) {
			$defaults = $this->get_defaults();
		}
		$schema = array(
			'type'       => 'object',
			'properties' => array(),
		);

		foreach ( $defaults as $module_key => $module_settings ) {
			$module_schema = array(
				'type'       => 'object',
				'properties' => array(),
			);

			foreach ( $module_settings as $key => $value ) {
				$module_schema['properties'][ $key ] = array( 'type' => $this->php_type_to_json( $value ) );
			}

			$schema['properties'][ $module_key ] = $module_schema;
		}

		return $schema;
	}

	/**
	 * Generate the client-safe configuration for the React UI.
	 *
	 * Strips PHP callbacks and server-only properties.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_client_array(): array {
		$tabs = array();

		$config_tabs = is_array( $this->config['tabs'] ?? null ) ? $this->config['tabs'] : array();
		foreach ( $config_tabs as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}

			$client_tab = array(
				'name'  => $tab['name'] ?? '',
				'title' => $tab['title'] ?? '',
			);

			if ( isset( $tab['type'] ) ) {
				$client_tab['type'] = $tab['type'];
			}

			if ( isset( $tab['component'] ) ) {
				$client_tab['component'] = $tab['component'];
			}

			if ( isset( $tab['intro'] ) ) {
				$client_tab['intro'] = $tab['intro'];
			}

			if ( isset( $tab['sections'] ) && is_array( $tab['sections'] ) ) {
				$client_tab['sections'] = array();

				foreach ( $tab['sections'] as $section ) {
					if ( ! is_array( $section ) ) {
						continue;
					}

					$client_section = array(
						'id'           => $section['id'] ?? '',
						'title'        => $section['title'] ?? '',
						'initial_open' => $section['initial_open'] ?? true,
					);

					if ( isset( $section['icon'] ) ) {
						$client_section['icon'] = $section['icon'];
					}

					if ( isset( $section['intro'] ) ) {
						$client_section['intro'] = $section['intro'];
					}

					if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
						$client_section['fields'] = array();

						foreach ( $section['fields'] as $field ) {
							if ( ! is_array( $field ) ) {
								continue;
							}

							$client_field = $this->field_to_client( $field );
							if ( $client_field ) {
								$client_section['fields'][] = $client_field;
							}
						}
					}

					$client_tab['sections'][] = $client_section;
				}
			}

			$tabs[] = $client_tab;
		}

		return array(
			'tabs'     => $tabs,
			'defaults' => $this->get_defaults(),
		);
	}

	/**
	 * Get all fields from all tabs and sections (flattened).
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_fields(): array {
		$fields = array();

		$config_tabs = is_array( $this->config['tabs'] ?? null ) ? $this->config['tabs'] : array();
		foreach ( $config_tabs as $tab ) {
			if ( ! is_array( $tab ) || ! isset( $tab['sections'] ) || ! is_array( $tab['sections'] ) ) {
				continue;
			}
			foreach ( $tab['sections'] as $section ) {
				if ( ! is_array( $section ) || ! isset( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
					continue;
				}
				foreach ( $section['fields'] as $field ) {
					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Convert a field definition to a client-safe array.
	 *
	 * Copies only whitelisted properties to prevent leaking server-side
	 * configuration (e.g. callbacks, validation rules) to the client.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 *
	 * @return array<string, mixed>|null
	 */
	private function field_to_client( array $field ): ?array {
		if ( ! isset( $field['key'], $field['type'] ) ) {
			return null;
		}

		$client = array(
			'key'   => $field['key'],
			'type'  => $field['type'],
			'label' => $field['label'] ?? '',
		);

		// Copy through safe properties.
		$safe_keys = array(
			'tooltip',
			'placeholder',
			'default',
			'min',
			'max',
			'units',
			'store_as',
			'options',
			'encrypted',
			'disabled',
			'rows',
			'language',
			'inline',
			'width',
			'show',
			'hide',
		);

		foreach ( $safe_keys as $safe_key ) {
			if ( isset( $field[ $safe_key ] ) ) {
				$client[ $safe_key ] = $field[ $safe_key ];
			}
		}

		return $client;
	}

	/**
	 * Map a PHP value to a JSON schema type string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The PHP value.
	 *
	 * @return string The JSON schema type.
	 */
	private function php_type_to_json( $value ): string {
		if ( is_bool( $value ) ) {
			return 'boolean';
		}
		if ( is_int( $value ) ) {
			return 'integer';
		}
		if ( is_float( $value ) ) {
			return 'number';
		}
		if ( is_array( $value ) ) {
			return 'array';
		}
		return 'string';
	}

	/**
	 * Normalize tabs into an associative array keyed by tab name, and
	 * sections within each tab keyed by section id.
	 *
	 * When multiple tabs share the same name, the last one wins. This
	 * allows add-on plugins to override tabs or individual sections via
	 * the `{$slug}_schema` filter.
	 *
	 * @since 1.1.0
	 *
	 * @param array<int|string, array<string, mixed>> $tabs The tabs array (numeric or associative).
	 *
	 * @return array<string, array<string, mixed>> Tabs keyed by name.
	 */
	private function normalize_tabs( array $tabs ): array {
		$keyed = array();

		foreach ( $tabs as $tab ) {
			if ( ! is_array( $tab ) || empty( $tab['name'] ) ) {
				continue;
			}

			$name = $tab['name'];

			// Normalize incoming sections to be keyed by id.
			$incoming_sections = array();
			if ( isset( $tab['sections'] ) && is_array( $tab['sections'] ) ) {
				foreach ( $tab['sections'] as $section ) {
					if ( ! is_array( $section ) || empty( $section['id'] ) ) {
						continue;
					}
					$incoming_sections[ $section['id'] ] = $section;
				}
			}

			if ( isset( $keyed[ $name ] ) && empty( $tab['replace'] ) ) {
				$existing_sections = $keyed[ $name ]['sections'];
				$tab['sections']   = array_replace( $existing_sections, $incoming_sections );
				$keyed[ $name ]    = array_replace( $keyed[ $name ], $tab );
			} else {
				unset( $tab['replace'] );
				$tab['sections'] = $incoming_sections;
				$keyed[ $name ]  = $tab;
			}
		}

		return $keyed;
	}
}
