---
title: 'Field Types'
post_excerpt: 'Reference for all 10 built-in field types: properties, sanitization, and JSON schema.'
menu_order: 10
---

# Field Types

MilliBase ships with 10 built-in field types. Each type provides server-side sanitization and a matching React component.

## Common Properties

Every field type accepts the following properties in addition to its type-specific options:

| Property  | Type     | Description                                                                                          |
|-----------|----------|------------------------------------------------------------------------------------------------------|
| `key`     | `string` | Required. Dot-notation identifier (e.g. `cache.ttl`).                                                |
| `type`    | `string` | Required. One of the type slugs documented below.                                                    |
| `label`   | `string` | Field label.                                                                                         |
| `tooltip` | `string` | Optional help text shown in a hover tooltip on a (?) icon next to the label.                         |
| `help`    | `string` | Optional description rendered below the input. Supported on `text`, `password`, `number`, `select`, `toggle`, `unit`, and `code`. On `token-list` the prop is forwarded but only takes effect once the host WordPress ships `@wordpress/components` ≥ 33.0.0; `color` and `button` do not support it. |
| `default` | `mixed`  | Default value used when no value has been saved yet.                                                 |

---

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
    'save' => 'seconds',
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
| `save` | `string` | Set to `'seconds'` for automatic time unit conversion |
| `min` | `int\|float` | Minimum allowed value |

**Time unit conversion:** When `save` is `'seconds'`, the value is saved in seconds but displayed in the most appropriate unit. For example, `3600` seconds displays as `1 h`.

Unit multipliers (for `save: 'seconds'`):

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

---

## button

Imperative action trigger — fires a custom REST action via `triggerAction()`. Buttons are not stateful settings: they have no value, no default, and do not appear in the persisted options or REST schema.

Place a button anywhere in `fields`. Combine with `inline: true` to position it next to another field (e.g. an "Activate" button beside a license-key input), or omit `inline` to render it on its own row.

```php
[
    'key'           => 'license.activate',
    'type'          => 'button',
    'label'         => 'Activate',
    'action'        => 'license_activate',
    'variant'       => 'primary',
    'size'          => 'compact',
    'icon'          => 'unlock',
    'isDestructive' => false,
    'inline'        => true,
    'show'          => [ 'license.is_valid', '!=', true ],
    'confirm'       => 'Activate this license?',
]
```

| Property        | Type                                               | Default       | Description                                                                                                              |
|-----------------|----------------------------------------------------|---------------|--------------------------------------------------------------------------------------------------------------------------|
| `action`        | `string` \| `string[]`                             | —             | Required. Name of a custom action — or an ordered chain of names — to trigger. See [Chained actions](#chained-actions).  |
| `variant`       | `'primary' \| 'secondary' \| 'tertiary' \| 'link'` | `'secondary'` | WordPress `Button` variant.                                                                                              |
| `size`          | `'default' \| 'compact' \| 'small'`                | `'default'`   | WordPress `Button` size. `'default'` enables the 40-pixel default-size opt-in automatically.                             |
| `isDestructive` | `bool`                                             | `false`       | Renders the button in destructive (red) styling.                                                                         |
| `icon`          | `string`                                           | —             | Icon name from `@wordpress/icons` (e.g. `'unlock'`, `'trash'`).                                                          |
| `confirm`       | `string`                                           | —             | Optional. If set, the button opens a `<Modal>` with this prompt and Cancel/Confirm buttons before triggering the action. |
| `tooltip`       | `string`                                           | —             | Hover tooltip text.                                                                                                      |
| `inline`        | `bool`                                             | `false`       | Place the button on the same row as the previous field.                                                                  |
| `width`         | `string`                                           | —             | CSS width applied to the inline-row flex item (e.g. `'200px'`, `'30%'`).                                                 |
| `show` / `hide` | `[field, op, value]`                               | —             | Conditional visibility against the current settings (e.g. `['license.is_valid', '=', true]`).                            |

**Action wiring:** the button's `action` must match the `name` of an entry in the config's `actions` array (which registers a REST endpoint), or be one of the framework-reserved built-ins listed below. See [Schema Definition → Actions](../02-usage/02-schema-definition.md) for full action registration.

**Busy/disabled state:** automatic — `isBusy` reflects the global loading state, and the button is disabled while saving or loading. Buttons are also disabled when the section's `active` toggle is off.

**Sanitization:** none — buttons are not persisted.
**JSON schema:** none — buttons do not appear in the REST settings schema.

> [!NOTE]
> Buttons require a `key` purely as a React identifier. The key is **not** read from or written to the settings store, even though it follows the standard `module.name` dot-notation.

### Chained actions

`action` accepts either a single name or an ordered list of names. A list runs sequentially, stops at the first non-success response, and surfaces a single trailing snackbar plus one settings/status refresh — so a chain reads as one operation to the user (one busy span, one toast, one refetch).

```php
[
    'key'    => 'license.activate',
    'type'   => 'button',
    'label'  => 'Activate',
    'action' => [ '__save', 'license_activate' ],
]
```

The example saves any pending field changes (the user pasted a key into a text input but hasn't clicked the global Save button yet), then runs the consumer-registered `license_activate` action against the saved state. If `__save` fails, `license_activate` is not invoked and the save error surfaces; if `license_activate` fails, the saved settings stand.

> [!NOTE]
> The chain primitive does not currently support per-step data. When `triggerAction` is invoked programmatically with a `data` payload (`triggerAction(['__save', 'foo'], { x: 1 })`), the same `data` is merged into every non-`__save` step's POST body. Callers that need divergent payloads per step should split the chain into separate `triggerAction` calls.

#### Built-in actions

The framework ships three built-in actions, named with a leading double-underscore (`__`) by convention to keep them visually distinct from consumer-registered actions. Consumer plugins are free to use any naming scheme; the only literal collisions to avoid are `__save`, `__reset`, and `__restore`.

| Name        | Effect                                                                                                                                                     |
|-------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `__save`    | Persists the current dirty settings against `/wp/v2/settings`. Silent no-op when there are no pending changes — safe to keep at the head of every chain.   |
| `__reset`   | Backs up the current option, then deletes it so defaults take over. Allow-list filterable via `{slug}_rest_settings_allowed_actions`.                      |
| `__restore` | Restores the most recent backup written by `__reset`. Returns a 400 `success: false` if no backup exists or the backup has expired.                        |

## Next Steps

- **[Custom Field Types](../03-customization/01-custom-field-types.md)** — register your own field types
- **[Schema Definition](../02-usage/02-schema-definition.md)** — field structure and conditional display
