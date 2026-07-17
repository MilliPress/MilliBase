---
title: 'Programmatic Access'
post_excerpt: 'Use the Settings API to read, write, import, export, and manage settings from PHP.'
menu_order: 30
---

# Programmatic Access

The `Settings` class provides the full API for reading and writing settings from PHP. Get it via `$manager->settings()`.

> **Note:** When you pass a `Settings` instance to the `Manager` constructor, `$manager->settings()` is available immediately — even before `init`. Schema-derived defaults are merged at construction time.

## Reading Settings

### Dot-Notation Access

```php
$settings = $manager->settings();

// Get a single value.
$ttl = $settings->get('cache.ttl');

// Get with a fallback default.
$host = $settings->get('storage.host', 'localhost');
```

### Get All Settings

```php
// Get all settings (merged from all sources).
$all = $settings->get();

// Get a specific module only.
$cache = $settings->get('cache');
// Returns: ['ttl' => 3600, 'enabled' => true]
```

### Settings Priority

`get()` merges settings from four sources in this priority order:

| Priority    | Source          | Description                        |
|-------------|-----------------|------------------------------------|
| 1 (highest) | **Constants**   | PHP constants from `wp-config.php` |
| 2           | **Config File** | PHP config file (if configured)    |
| 3           | **Database**    | WordPress `wp_options` table       |
| 4 (lowest)  | **Defaults**    | Schema-extracted defaults          |

```php
// Check where a specific setting comes from.
$source = $settings->get_source('cache', 'ttl');
// Returns: 'constant', 'file', 'db', or 'default'
```

Use `get_source()` when you need to distinguish constant-defined values from editable ones (e.g. to render a field as read-only in a custom UI). On the network-mode side, the same `get_source()` call works against `wp_sitemeta` data transparently.

### Default Settings

```php
// Get all defaults (includes filter modifications).
$defaults = $settings->get_default_settings();

// Get defaults for a specific module.
$cache_defaults = $settings->get_default_settings('cache');

// Check if current settings match defaults.
$is_default = $settings->has_default_settings();
```

## Writing Settings

### Dot-Notation Set

```php
// Set a single value.
$settings->set('cache.ttl', 7200);

// Keys must have at least 2 levels (module.key).
$settings->set('cache.ttl', 7200);       // OK
$settings->set('ttl', 7200);             // Returns false
```

### Import / Export

```php
// Export all settings (encrypted fields stripped).
$export = $settings->export();

// Export with decrypted sensitive fields.
$export = $settings->export(null, true);

// Export a specific module.
$export = $settings->export('cache');

// Import settings (merged with existing).
$settings->import([
    'cache' => ['ttl' => 7200, 'enabled' => true],
]);

// Import and replace (no merge).
$settings->import($data, false);
```

> [!NOTE]
> Import only accepts modules that exist in the schema defaults. Unknown modules are silently discarded. The `host` module is always excluded from exports.

## Backup and Restore

```php
// Create a backup (stored as a transient, expires in 3 days).
$settings->backup();

// Check if a backup exists.
$has_backup = $settings->has_backup();

// Restore from backup (deletes the transient on success).
$restored = $settings->restore_backup();
```

## Reset

```php
// Reset all settings to defaults.
$settings->reset();

// Reset a specific module only.
$settings->reset('cache');
```

## Standalone Mode

For scenarios where you need settings before WordPress loads (e.g. in `advanced-cache.php`), create a standalone Settings instance:

```php
$settings = \MilliBase\Settings::standalone([
    'option_name'     => 'my_plugin_settings',
    'constant_prefix' => 'MP',
    'defaults'        => [
        'cache' => ['ttl' => 3600, 'enabled' => true],
    ],
    'config_file'     => [
        'directory' => '/path/to/config',
    ],
]);

// Reads from config file and constants only — no database.
$ttl = $settings->get('cache.ttl');
```

## Constants Override

When `constant_prefix` is set, you can override settings via PHP constants in `wp-config.php`:

```php
// constant_prefix: 'MP'
// Field key: cache.ttl
// Constant: MP_CACHE_TTL

define('MP_CACHE_TTL', 7200);
define('MP_CACHE_ENABLED', true);
```

The constant name follows the pattern `{PREFIX}_{MODULE}_{KEY}` (all uppercase). Constant-defined settings:

- Take highest priority
- Are excluded from the stored database value
- Render as disabled (read-only) in the React UI
- Show the resolved constant value from the status endpoint

## Type Coercion

The static `Settings::coerce_value()` method converts string values to appropriate PHP types. This is primarily used for constants:

```php
Settings::coerce_value('true');    // bool true
Settings::coerce_value('false');   // bool false
Settings::coerce_value('null');    // null
Settings::coerce_value('42');      // int 42
Settings::coerce_value('3.14');    // float 3.14
Settings::coerce_value('hello');   // string 'hello'
```

## Logging

The `Logger` class provides a channel-prefixed wrapper around `error_log()` so consuming plugins never call `error_log()` directly — keeping PHPCS and Plugin Check findings confined to one auditable location. Create one instance per plugin with a channel name (typically the plugin name):

```php
$log = new \MilliBase\Logger('MilliCache');

$log->error('Storage connection lost');
$log->warning('Falling back to disk cache', ['reason' => 'timeout']);
$log->debug('Cache key resolved', ['key' => 'home']);
```

Each entry is written as `[{channel}] [{level}] {message}`, with any structured context appended as JSON:

```
[MilliCache] [error] Storage connection lost
[MilliCache] [warning] Falling back to disk cache {"reason":"timeout"}
```

| Method | Level | When written |
|--------|-------|--------------|
| `error()`   | `error`   | Always — signals an incident needing attention |
| `warning()` | `warning` | Always — recoverable or degraded-operation notice |
| `debug()`   | `debug`   | Only when `WP_DEBUG` is enabled |

The logger is safe to use before WordPress has fully loaded (e.g. from an `advanced-cache.php` drop-in): WordPress functions such as `wp_json_encode()` and `do_action()` are feature-detected and skipped when unavailable.

Every written entry also fires the [`millibase_log`](../04-reference/03-hooks-and-filters.md#millibase_log) action, so a dashboard or external service can capture entries without parsing `debug.log`.

## Next Steps

- **[Custom Field Types](../03-customization/01-custom-field-types.md)** — extend the UI with custom fields
- **[Custom Tab Components](../03-customization/02-custom-tab-components.md)** — render custom React content in tabs
- **[Hooks and Filters](../04-reference/03-hooks-and-filters.md)** — all available hooks
