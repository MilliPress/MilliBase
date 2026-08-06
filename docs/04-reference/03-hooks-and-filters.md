---
title: 'Hooks and Filters'
post_excerpt: 'Complete reference of all WordPress hooks and filters fired by MilliBase.'
menu_order: 30
---

# Hooks and Filters

MilliBase uses WordPress hooks for extensibility. The `{slug}` placeholder refers to your config's `slug` value, and `{option_name}` refers to the `option_name` value.

## Filters

### `{slug}_settings_schema`

Fires before Schema initialization. This filter fires twice per request:

1. **At construction time** (before `init`) — with a minimal config (`['tabs' => []]`) to extract defaults for early Settings access
2. **On `init`** — with the full config (including translated strings) for UI rendering

Add-on filters may modify any config keys, but must gracefully handle the early phase where only the `tabs` key is present.

```php
add_filter('my_plugin_settings_schema', function (array $config): array {
    // Add or modify tabs/sections/fields.
    return $config;
});
```

**Parameters:** `array $config` — the settings configuration array.

---

### `{slug}_settings_defaults`

Modify default settings at runtime. Can fire before `init` when Settings is accessed early — callbacks should not depend on other plugins or translated strings being available.

```php
add_filter('my_plugin_settings_defaults', function (array $defaults, bool $is_network): array {
    if (! $is_network) {
        $defaults['cache']['ttl'] = 7200;
    }
    return $defaults;
}, 10, 2);
```

**Parameters:**
- `array $defaults` — default settings keyed by module
- `bool $is_network` — whether this Settings instance is network-scoped (since 2.5.1)

---

### `{slug}_rest_settings_allowed_actions`

Filter the list of allowed action names for the built-in settings action endpoint.

```php
add_filter('my_plugin_rest_settings_allowed_actions', function (array $allowed): array {
    $allowed[] = 'purge-cache';
    return $allowed;
});
```

**Parameters:** `array $allowed` — action name strings. Default: `['__reset', '__restore']`.

---

### `{slug}_rest_status_response`

Modify the status endpoint response before it is returned.

```php
add_filter('my_plugin_rest_status_response', function (array $status, \WP_REST_Request $request, bool $is_network): array {
    $status['extra_info'] = 'value';
    return $status;
}, 10, 3);
```

**Parameters:**
- `array $status` — the status data (includes `settings.has_defaults`, `settings.has_backup`, `settings.constants`)
- `\WP_REST_Request $request` — the REST request object
- `bool $is_network` — whether this Controller is network-scoped (since 2.5.1)

**Reserved keys:**
- `poll_interval` (int, seconds) — requests a faster status-poll cadence from the settings UI while background work is in flight. Clamped to 2–60 seconds client-side; omit it to use the 15-second default. When several producers set it, use `min()` against the existing value so the fastest request wins:

```php
$status['poll_interval'] = min($status['poll_interval'] ?? 15, 5);
```

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

Fires after a built-in settings action (`__reset`, `__restore`) has been successfully performed.

```php
add_action('my_plugin_rest_settings_action_performed', function (string $action, array $params, \WP_REST_Request $request): void {
    if ($action === '__reset') {
        // Clean up after reset.
    }
}, 10, 3);
```

**Parameters:**
- `string $action` — the action that was performed (`'__reset'`, `'__restore'`)
- `array $params` — the request parameters
- `\WP_REST_Request $request` — the REST request object

> [!NOTE]
> This hook does **not** fire for the chain-mode `__save` step. `__save` writes through WordPress core's `/wp/v2/settings` endpoint, not the framework's namespaced settings endpoint, so it bypasses `Controller::perform_settings_action`. To observe saves regardless of trigger (Save button, `__save` step, programmatic `update_option`), hook the WordPress-native `update_option_{option_name}` / `add_option_{option_name}` actions, or use the per-key `{slug}_setting_changed/{dot_key}` action below.

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

---

### `millibase_log`

Fires for every entry written by the [`Logger`](../02-usage/03-programmatic-access.md#logging). Unlike the hooks above, this action is **not** `{slug}`-prefixed — it is shared across every plugin that uses MilliBase's logger, and the `$channel` argument identifies the source. Use it to mirror entries into an additional sink, such as a persistent store backing a dashboard log view.

```php
add_action('millibase_log', function (string $channel, string $level, string $message, array $context): void {
    if ($level === \MilliBase\Logger::ERROR) {
        // Persist errors for a dashboard, forward to an external service, etc.
    }
}, 10, 4);
```

**Parameters:**
- `string $channel` — the channel name (typically the plugin name, e.g. `'MilliCache'`)
- `string $level` — the entry level (`'error'`, `'warning'`, or `'debug'`)
- `string $message` — the log message
- `array $context` — structured context, or an empty array

> [!NOTE]
> Because `debug` entries are gated behind `WP_DEBUG`, this action does **not** fire for them unless `WP_DEBUG` is enabled. `error` and `warning` entries always fire.

## REST Endpoints

MilliBase registers these REST routes:

| Method | Route | Description |
|--------|-------|-------------|
| `POST` | `/wp/v2/settings` | Save settings (WordPress native) |
| `POST` | `/{rest_namespace}/settings` | Built-in actions (`__reset`, `__restore`) |
| `GET` | `/{rest_namespace}/status` | Status endpoint (always registered; enriched by `status.data` and `status.callback`) |
| varies | `/{rest_namespace}/{endpoint}` | Custom action endpoints |

All endpoints require the configured `capability` (default: `manage_options`). Non-GET requests require a valid `X-WP-Nonce` header.

## Next Steps

- **[Extending with Filters](../03-customization/03-extending-with-filters.md)** — practical examples
- **[Settings API](./02-settings-api.md)** — full Settings method reference
