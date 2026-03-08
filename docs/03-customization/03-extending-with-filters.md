---
title: 'Extending with Filters'
post_excerpt: 'Use WordPress filters to modify the schema, defaults, actions, and status response.'
menu_order: 30
---

# Extending with Filters

MilliBase fires several WordPress filters that allow add-on plugins to modify behavior without touching the original plugin code.

## Schema Filter

The `{slug}_settings_schema` filter fires before the Schema is initialized. Use it to add tabs, sections, or fields from an add-on plugin:

```php
add_filter('my_plugin_settings_schema', function (array $config): array {
    // Add a new tab.
    $config['tabs'][] = [
        'name'     => 'addon',
        'title'    => 'My Add-on',
        'sections' => [
            [
                'id'     => 'addon-settings',
                'title'  => 'Add-on Settings',
                'fields' => [
                    [
                        'key'     => 'addon.enabled',
                        'type'    => 'toggle',
                        'label'   => 'Enable Add-on',
                        'default' => false,
                    ],
                ],
            ],
        ],
    ];

    return $config;
});
```

### Overriding Existing Sections

Because tabs are keyed by `name` and sections by `id`, you can override existing sections by using the same identifiers:

```php
add_filter('my_plugin_settings_schema', function (array $config): array {
    // Add a field to the existing 'general' tab, 'main' section.
    $config['tabs'][] = [
        'name'     => 'general',
        'sections' => [
            [
                'id'     => 'main',
                'title'  => 'Main Settings',
                'fields' => [
                    [
                        'key'     => 'general.extra_option',
                        'type'    => 'text',
                        'label'   => 'Extra Option',
                        'default' => '',
                    ],
                ],
            ],
        ],
    ];
    return $config;
});
```

> [!NOTE]
> When sections share the same `id`, they are merged — the last section's properties overwrite earlier ones, and fields are combined. To fully replace a tab instead of merging, add `'replace' => true` to the tab definition.

> [!TIP]
> The schema filter is the recommended way for add-ons to register new settings. Defaults are extracted automatically from the `default` property on each field — no need to also use the defaults filter.

## Defaults Filter

The `{slug}_settings_defaults` filter lets you modify default values at runtime. This is primarily useful for non-UI settings that need to exist in the stored option but don't have a corresponding field in the schema:

```php
add_filter('my_plugin_settings_defaults', function (array $defaults): array {
    // Override a default.
    $defaults['cache']['ttl'] = 7200;

    // Add defaults for non-UI settings (no corresponding schema field).
    $defaults['internal'] = [
        'migration_version' => 0,
        'install_date'      => '',
    ];

    return $defaults;
});
```

> [!NOTE]
> Settings added only via the defaults filter will not appear in the UI. To add settings with both UI fields and defaults, use the schema filter instead — defaults are extracted automatically from field definitions.

## Allowed Actions Filter

The `{slug}_rest_settings_allowed_actions` filter controls which built-in actions are permitted via the REST endpoint:

```php
add_filter('my_plugin_rest_settings_allowed_actions', function (array $allowed): array {
    // Add a custom action name.
    $allowed[] = 'purge-cache';

    return $allowed;
});
```

By default, only `['reset', 'restore']` are allowed.

## Status Response Filter

The `{slug}_rest_status_response` filter modifies the status endpoint response before it is returned:

```php
add_filter('my_plugin_rest_status_response', function (array $status, \WP_REST_Request $request): array {
    $status['addon_version'] = '2.1.0';

    return $status;
}, 10, 2);
```

## Action Performed Hook

The `{slug}_rest_settings_action_performed` action fires after a built-in action completes:

```php
add_action('my_plugin_rest_settings_action_performed', function (string $action, array $params, \WP_REST_Request $request): void {
    if ($action === 'reset') {
        // Perform cleanup after settings reset.
        delete_transient('my_plugin_cache');
    }
}, 10, 3);
```

## Filter Reference

| Filter / Action | Parameters | Description |
|----------------|------------|-------------|
| `{slug}_settings_schema` | `(array $config)` | Modify the full config before Schema init |
| `{slug}_settings_defaults` | `(array $defaults)` | Modify default settings |
| `{slug}_rest_settings_allowed_actions` | `(array $allowed)` | Filter allowed REST action names |
| `{slug}_rest_status_response` | `(array $status, WP_REST_Request $request)` | Modify status response |
| `{slug}_rest_settings_action_performed` | `(string $action, array $params, WP_REST_Request $request)` | Fires after an action |

## Next Steps

- **[Hooks and Filters](../04-reference/03-hooks-and-filters.md)** — complete hooks reference including Settings hooks
- **[Configuration](../02-usage/01-configuration.md)** — full configuration options
