---
title: 'Programmatic Access'
post_excerpt: 'Use the Settings API to read, write, import, export, and manage settings from PHP.'
menu_order: 30
---

# Programmatic Access

The `Settings` class provides the full API for reading and writing settings from PHP. Get it via `$manager->settings()`.

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
$all = $settings->get_all();

// Get a specific module only.
$cache = $settings->get_all('cache');
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
$editable = $settings->get_all(null, true);

// Check where a specific setting comes from.
$source = $settings->get_source('cache', 'ttl');
// Returns: 'constant', 'file', 'db', or 'default'
```

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
// Create a backup (stored as a transient, expires in 12 hours).
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

## Next Steps

- **[Custom Field Types](../03-customization/01-custom-field-types.md)** — extend the UI with custom fields
- **[Custom Tab Components](../03-customization/02-custom-tab-components.md)** — render custom React content in tabs
- **[Hooks and Filters](../04-reference/03-hooks-and-filters.md)** — all available hooks
