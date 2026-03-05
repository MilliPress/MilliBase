---
title: 'Programmatic Access'
post_excerpt: 'Use the Store API to read, write, import, export, and manage settings from PHP.'
menu_order: 30
---

# Programmatic Access

The `Store` class provides the full API for reading and writing settings from PHP. Get it via `$settings->store()`.

## Reading Settings

### Dot-Notation Access

```php
$store = $settings->store();

// Get a single value.
$ttl = $store->get('cache.ttl');

// Get with a fallback default.
$host = $store->get('storage.host', 'localhost');
```

### Get All Settings

```php
// Get all settings (merged from all sources).
$all = $store->get_all();

// Get a specific module only.
$cache = $store->get_all('cache');
// Returns: ['cache' => ['ttl' => 3600, 'enabled' => true]]
```

### Settings Priority

`get_all()` merges settings from four sources in this priority order:

| Priority | Source | Description |
|----------|--------|-------------|
| 1 (highest) | **Constants** | PHP constants from `wp-config.php` |
| 2 | **Config File** | PHP config file (if configured) |
| 3 | **Database** | WordPress `wp_options` table |
| 4 (lowest) | **Defaults** | Schema-extracted defaults |

```php
// Skip constants to get the "editable" value.
$editable = $store->get_all(null, true);

// Check where a specific setting comes from.
$source = $store->get_source('cache', 'ttl');
// Returns: 'constant', 'file', 'db', or 'default'
```

### Default Settings

```php
// Get all defaults (includes filter modifications).
$defaults = $store->get_default_settings();

// Get defaults for a specific module.
$cache_defaults = $store->get_default_settings('cache');

// Check if current settings match defaults.
$is_default = $store->has_default_settings();
```

## Writing Settings

### Dot-Notation Set

```php
// Set a single value.
$store->set('cache.ttl', 7200);

// Keys must have at least 2 levels (module.key).
$store->set('cache.ttl', 7200);       // OK
$store->set('ttl', 7200);             // Returns false
```

### Import / Export

```php
// Export all settings (encrypted fields stripped).
$export = $store->export();

// Export with decrypted sensitive fields.
$export = $store->export(null, true);

// Export a specific module.
$export = $store->export('cache');

// Import settings (merged with existing).
$store->import([
    'cache' => ['ttl' => 7200, 'enabled' => true],
]);

// Import and replace (no merge).
$store->import($data, false);
```

> [!NOTE]
> Import only accepts modules that exist in the schema defaults. Unknown modules are silently discarded. The `host` module is always excluded from exports.

## Backup and Restore

```php
// Create a backup (stored as a transient, expires in 12 hours).
$store->backup();

// Check if a backup exists.
$has_backup = $store->has_backup();

// Restore from backup (deletes the transient on success).
$restored = $store->restore_backup();
```

## Reset

```php
// Reset all settings to defaults.
$store->reset();

// Reset a specific module only.
$store->reset('cache');
```

## Standalone Mode

For scenarios where you need settings before WordPress loads (e.g. in `advanced-cache.php`), create a standalone Store:

```php
$store = \MilliBase\Store::standalone([
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
$ttl = $store->get('cache.ttl');
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

The static `Store::coerce_value()` method converts string values to appropriate PHP types. This is primarily used for constants:

```php
Store::coerce_value('true');    // bool true
Store::coerce_value('false');   // bool false
Store::coerce_value('null');    // null
Store::coerce_value('42');      // int 42
Store::coerce_value('3.14');    // float 3.14
Store::coerce_value('hello');   // string 'hello'
```

## Next Steps

- **[Custom Field Types](../03-customization/01-custom-field-types.md)** — extend the UI with custom fields
- **[Custom Tab Components](../03-customization/02-custom-tab-components.md)** — render custom React content in tabs
- **[Hooks and Filters](../04-reference/03-hooks-and-filters.md)** — all available hooks
