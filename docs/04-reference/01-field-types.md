---
title: 'Field Types'
post_excerpt: 'Reference for all 9 built-in field types: properties, sanitization, and JSON schema.'
menu_order: 10
---

# Field Types

MilliBase ships with 9 built-in field types. Each type provides server-side sanitization and a matching React component.

## text

Standard text input. Sanitized via `sanitize_text_field()`.

```php
[
    'key'         => 'general.site_name',
    'type'        => 'text',
    'label'       => 'Site Name',
    'default'     => '',
    'placeholder' => 'Enter site name',
    'tooltip'     => 'The display name for your site.',
]
```

| Property | Type | Description |
|----------|------|-------------|
| `placeholder` | `string` | Placeholder text |
| `tooltip` | `string` | Help text in tooltip icon |

**Sanitization:** Strips HTML tags and extra whitespace via `sanitize_text_field()`.
**JSON schema:** `{ "type": "string" }`

---

## number

Numeric input with optional min/max bounds.

```php
[
    'key'     => 'cache.ttl',
    'type'    => 'number',
    'label'   => 'Cache TTL',
    'default' => 3600,
    'min'     => 60,
    'max'     => 86400,
]
```

| Property | Type | Description |
|----------|------|-------------|
| `min` | `int\|float` | Minimum allowed value |
| `max` | `int\|float` | Maximum allowed value |

**Sanitization:** Converts to numeric type, clamps to `min`/`max` bounds.
**JSON schema:** `{ "type": "number", "minimum": 60, "maximum": 86400 }`

---

## password

Password input. Stores the raw value without HTML stripping (important for tokens and API keys containing special characters).

```php
[
    'key'         => 'storage.enc_password',
    'type'        => 'password',
    'label'       => 'Storage Password',
    'default'     => '',
    'placeholder' => '••••••••',
]
```

**Sanitization:** Preserves the raw string value (no HTML stripping).
**JSON schema:** `{ "type": "string" }`

> [!TIP]
> Prefix the key with `enc_` (e.g. `storage.enc_password`) to enable automatic encryption. The value will be encrypted with sodium before saving and decrypted on read.

---

## toggle

Boolean toggle switch.

```php
[
    'key'     => 'cache.enabled',
    'type'    => 'toggle',
    'label'   => 'Enable Cache',
    'default' => true,
    'tooltip' => 'Toggle full-page caching on or off.',
]
```

**Sanitization:** Casts to boolean.
**JSON schema:** `{ "type": "boolean" }`

---

## select

Dropdown select with validation against a whitelist of allowed values.

```php
[
    'key'     => 'general.log_level',
    'type'    => 'select',
    'label'   => 'Log Level',
    'default' => 'warning',
    'options' => [
        ['label' => 'Debug',   'value' => 'debug'],
        ['label' => 'Info',    'value' => 'info'],
        ['label' => 'Warning', 'value' => 'warning'],
        ['label' => 'Error',   'value' => 'error'],
    ],
]
```

| Property | Type | Description |
|----------|------|-------------|
| `options` | `array` | Array of `{label, value}` objects |

**Sanitization:** Validates against the `options` whitelist. Falls back to `default` if the submitted value is not in the list.
**JSON schema:** `{ "type": "string", "enum": ["debug", "info", "warning", "error"] }`

---

## unit

Numeric input with a CSS unit selector. Supports automatic conversion to/from seconds for time-based values.

```php
[
    'key'      => 'cache.ttl',
    'type'     => 'unit',
    'label'    => 'Cache TTL',
    'default'  => 3600,
    'store_as' => 'seconds',
    'min'      => 0,
    'units'    => [
        ['label' => 'Seconds', 'value' => 's'],
        ['label' => 'Minutes', 'value' => 'm'],
        ['label' => 'Hours',   'value' => 'h'],
        ['label' => 'Days',    'value' => 'd'],
    ],
]
```

| Property | Type | Description |
|----------|------|-------------|
| `units` | `array` | Array of `{label, value}` unit options |
| `store_as` | `string` | Set to `'seconds'` for automatic time unit conversion |
| `min` | `int\|float` | Minimum allowed value |

**Time unit conversion:** When `store_as` is `'seconds'`, the value is stored in seconds but displayed in the most appropriate unit. For example, `3600` seconds displays as `1 h`.

Unit multipliers (for `store_as: 'seconds'`):

| Unit | Multiplier |
|------|------------|
| `s` | 1 |
| `m` | 60 |
| `h` | 3600 |
| `d` | 86400 |
| `w` | 604800 |
| `M` | 2592000 |

**Default units** (when `units` is not specified): Seconds, Minutes, Hours, Days.

**Sanitization:** Converts to numeric type.
**JSON schema:** `{ "type": "number" }`

---

## token-list

Multi-value input for entering a list of string tokens (tags, domains, paths, etc.).

```php
[
    'key'         => 'cache.excluded_paths',
    'type'        => 'token-list',
    'label'       => 'Excluded Paths',
    'default'     => [],
    'placeholder' => 'Add a path and press Enter',
]
```

| Property | Type | Description |
|----------|------|-------------|
| `placeholder` | `string` | Placeholder text in the input |

**Sanitization:** Each token is sanitized via `sanitize_text_field()`, empty tokens are removed.
**JSON schema:** `{ "type": "array", "items": { "type": "string" } }`

---

## color

Color picker with hex color validation.

```php
[
    'key'     => 'general.accent_color',
    'type'    => 'color',
    'label'   => 'Accent Color',
    'default' => '#0073aa',
]
```

**Sanitization:** Validates hex color format (`#RGB`, `#RRGGBB`, `#RRGGBBAA`). Uses `sanitize_hex_color()` when available, falls back to regex.
**JSON schema:** `{ "type": "string" }`

---

## code

Multi-line textarea for code input. Stores the raw value without sanitization (no HTML stripping).

```php
[
    'key'      => 'advanced.custom_css',
    'type'     => 'code',
    'label'    => 'Custom CSS',
    'default'  => '',
    'rows'     => 10,
    'language' => 'css',
]
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `rows` | `int` | `6` | Number of textarea rows |
| `language` | `string` | — | Syntax language hint |

**Sanitization:** Preserves the raw string value (no HTML stripping or escaping).
**JSON schema:** `{ "type": "string" }`

> [!CAUTION]
> The `code` field type stores raw input. If you output the value in HTML, ensure proper escaping.

## Next Steps

- **[Custom Field Types](../03-customization/01-custom-field-types.md)** — register your own field types
- **[Schema Definition](../02-usage/02-schema-definition.md)** — field structure and conditional display
