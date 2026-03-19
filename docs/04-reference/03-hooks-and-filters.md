---
title: 'Hooks and Filters'
post_excerpt: 'Complete reference of all WordPress hooks and filters fired by MilliBase.'
menu_order: 30
---

# Hooks and Filters

MilliBase uses WordPress hooks for extensibility. The `{slug}` placeholder refers to your config's `slug` value, and `{option_name}` refers to the `option_name` value.

## Filters

### `{slug}_settings_schema`

Fires before Schema initialization. Modify the full configuration array to add tabs, sections, or fields.

```php
add_filter('my_plugin_settings_schema', function (array $config): array {
    // Add or modify tabs/sections/fields.
    return $config;
});
```

**Parameters:** `array $config` — the full settings configuration array.

---

### `{slug}_settings_defaults`

Modify default settings at runtime.

```php
add_filter('my_plugin_settings_defaults', function (array $defaults): array {
    $defaults['cache']['ttl'] = 7200;
    return $defaults;
});
```

**Parameters:** `array $defaults` — default settings keyed by module.

---

### `{slug}_rest_settings_allowed_actions`

Filter the list of allowed action names for the built-in settings action endpoint.

```php
add_filter('my_plugin_rest_settings_allowed_actions', function (array $allowed): array {
    $allowed[] = 'purge-cache';
    return $allowed;
});
```

**Parameters:** `array $allowed` — action name strings. Default: `['reset', 'restore']`.

---

### `{slug}_rest_status_response`

Modify the status endpoint response before it is returned.

```php
add_filter('my_plugin_rest_status_response', function (array $status, \WP_REST_Request $request): array {
    $status['extra_info'] = 'value';
    return $status;
}, 10, 2);
```

**Parameters:**
- `array $status` — the status data (includes `settings.has_defaults`, `settings.has_backup`, `settings.constants`)
- `\WP_REST_Request $request` — the REST request object

---

### `option_{option_name}`

WordPress core filter. MilliBase hooks into this to:

- Strip constant-defined keys from the stored value
- Merge with defaults (add missing keys, remove obsolete ones)

---

### `default_option_{option_name}`

WordPress core filter. Same processing as `option_{option_name}` — ensures the default value is schema-conformant.

---

### `pre_update_option_{option_name}`

WordPress core filter. When encryption is enabled, MilliBase hooks into this to encrypt `enc_*` fields before they are saved to the database.

## Actions

### `{slug}_rest_settings_action_performed`

Fires after a built-in settings action (reset, restore) has been successfully performed.

```php
add_action('my_plugin_rest_settings_action_performed', function (string $action, array $params, \WP_REST_Request $request): void {
    if ($action === 'reset') {
        // Clean up after reset.
    }
}, 10, 3);
```

**Parameters:**
- `string $action` — the action that was performed (`'reset'`, `'restore'`)
- `array $params` — the request parameters
- `\WP_REST_Request $request` — the REST request object

---

### `{slug}_setting_changed/{dot_key}`

Fires once per changed key whenever settings are saved (via `add_option` or `update_option`). The key uses dot notation matching `Settings::get()` syntax.

```php
add_action('my_plugin_setting_changed/warming.enabled', function ($new_value, $old_value, string $key): void {
    if ($new_value) {
        // Module was just enabled — prefetch sitemap URLs, etc.
    }
}, 10, 3);
```

**Parameters:**
- `mixed $new_value` — the new value (`null` if the key was removed)
- `mixed $old_value` — the old value (`null` if the key is new)
- `string $key` — the dot-notation key that changed (e.g. `warming.enabled`)

---

### `{slug}_setting_changed`

Fires once per save when at least one setting key changed. Useful for batch operations (e.g. flushing caches once after multiple changes).

```php
add_action('my_plugin_setting_changed', function (array $changes, array $new_settings, array $old_settings): void {
    if (isset($changes['cache.ttl'])) {
        // TTL changed from $changes['cache.ttl']['old'] to $changes['cache.ttl']['new'].
    }
}, 10, 3);
```

**Parameters:**
- `array $changes` — changed keys as `['dot.key' => ['old' => mixed, 'new' => mixed], ...]`
- `array $new_settings` — the full new settings array
- `array $old_settings` — the full old settings array

---

### `add_option_{option_name}`

WordPress core action. MilliBase hooks into this to sync settings to the config file and fire setting change hooks when the option is first created.

---

### `update_option_{option_name}`

WordPress core action. MilliBase hooks into this to sync settings to the config file and fire setting change hooks when the option is updated.

---

### `delete_option`

WordPress core action. MilliBase hooks into this to delete the config file when the matching option is deleted.

## REST Endpoints

MilliBase registers these REST routes:

| Method | Route | Description |
|--------|-------|-------------|
| `POST` | `/wp/v2/settings` | Save settings (WordPress native) |
| `POST` | `/{rest_namespace}/settings` | Built-in actions (reset, restore) |
| `GET` | `/{rest_namespace}/status` | Status endpoint (always registered; enriched by `status.data` and `status.callback`) |
| varies | `/{rest_namespace}/{endpoint}` | Custom action endpoints |

All endpoints require the configured `capability` (default: `manage_options`). Non-GET requests require a valid `X-WP-Nonce` header.

## Next Steps

- **[Extending with Filters](../03-customization/03-extending-with-filters.md)** — practical examples
- **[Settings API](./02-settings-api.md)** — full Settings method reference
