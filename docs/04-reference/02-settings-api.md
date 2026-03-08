---
title: 'Settings API'
post_excerpt: 'Complete API reference for the Settings class: methods, parameters, and return types.'
menu_order: 20
---

# Settings API

The `Settings` class handles all settings persistence. Access it via `$manager->settings()`.

## Constructor

```php
new Settings(array $config)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | `string` | Plugin slug for hook naming (default: `''`) |
| `option_name` | `string` | WordPress option name (default: `{slug}_settings`) |
| `constant_prefix` | `string` | Prefix for PHP constant overrides (default: `''`) |
| `encryption` | `bool` | Enable sodium encryption for `enc_*` fields (default: `false`) |
| `defaults` | `array` | Default settings keyed by module |
| `config_file` | `array\|false` | Config file settings with `directory` key, or `false` to disable |
| `standalone` | `bool` | Standalone mode — no database access (default: `false`) |

## Static Factory

### `Settings::standalone(array $config): Settings`

Creates a Settings instance that reads from config files and constants only — no database access. Useful for pre-WordPress scenarios like `advanced-cache.php`.

## Reading

### `get(string $key, mixed $default = null): mixed`

Get a value using dot notation.

```php
$settings->get('cache.ttl');              // 3600
$settings->get('cache.ttl', 7200);        // 7200 if not set
$settings->get('cache');                   // ['ttl' => 3600, ...]
```

### `get_all(?string $module = null, bool $skip_constants = false): array`

Get merged settings from all sources. Priority: Constants > Config File > Database > Defaults.

```php
$settings->get_all();                     // All settings
$settings->get_all('cache');              // ['cache' => [...]]
$settings->get_all(null, true);           // All settings, skip constants
```

### `get_default_settings(?string $module = null): array`

Get default settings. Applies the `{slug}_settings_defaults` filter.

### `get_settings_from_constants(?string $module = null): array`

Get settings defined as PHP constants.

### `get_settings_from_db(?string $module = null): array`

Get settings from the WordPress database. Returns empty array in standalone mode.

### `get_source(string $module, string $key): string`

Get the source of a setting value. Returns `'constant'`, `'file'`, `'db'`, or `'default'`.

### `has_default_settings(): bool`

Check if current settings (excluding constants) match the defaults.

### `get_option_name(): string`

Get the WordPress option name.

## Writing

### `set(string $key, mixed $value): bool`

Set a value using dot notation. The key must have at least 2 levels (`module.key`).

```php
$settings->set('cache.ttl', 7200);        // true
$settings->set('ttl', 7200);              // false (no module)
```

### `reset(?string $module = null): bool`

Reset settings to defaults. Pass a module name to reset only that module.

### `import(array $settings, bool $merge = true): bool`

Import settings. Only modules present in the schema defaults are accepted. Unknown modules are discarded.

### `export(?string $module = null, bool $include_encrypted = false): array`

Export settings. Encrypted fields are stripped unless `$include_encrypted` is `true` (in which case they are decrypted). The `host` module is always excluded.

## Backup / Restore

### `backup(?string $module = null): void`

Save current settings to a transient (expires in 12 hours).

### `has_backup(): bool`

Check if a backup transient exists.

### `restore_backup(): bool`

Restore settings from backup. Deletes the transient on success. Returns `false` if no backup exists.

## Encryption

### `encrypt_sensitive_settings_data(array $settings): array`

Encrypt all fields with keys starting with `enc_`. Uses sodium with `AUTH_KEY` + `SECURE_AUTH_KEY` as key material.

### `decrypt_sensitive_settings_data(array $settings): array`

Decrypt all `enc_*` fields.

### `Settings::encrypt_value(string $value): string` (static)

Encrypt a single value. Returns the value prefixed with `ENC:`. Already-encrypted values (prefixed with `ENC:`) are returned as-is.

### `Settings::decrypt_value(string $encrypted_value): string|false` (static)

Decrypt a single value. Non-encrypted values are returned as-is.

### `Settings::coerce_value(string $value): mixed` (static)

Convert a string to its appropriate PHP type (`'true'` → `true`, `'42'` → `42`, etc.).

## Hook Callbacks

These methods are registered as WordPress hook callbacks by `register_hooks()`:

### `filter_settings_by_constants(array|false $settings): array`

Hooked into `option_{name}` and `default_option_{name}`. Strips constant-defined keys from the stored value, merges with defaults, and removes obsolete keys.

### `on_add_option(string $option, array $settings): void`

Syncs to config file when the option is first created.

### `on_update_option(array $old, array $new): void`

Syncs to config file when the option is updated.

### `on_delete_option(string $option): void`

Deletes the config file when the option is deleted.

## Next Steps

- **[Hooks and Filters](./03-hooks-and-filters.md)** — all hooks fired by MilliBase
- **[Programmatic Access](../02-usage/03-programmatic-access.md)** — usage examples
