---
title: 'Custom Field Types'
post_excerpt: 'Register custom field types to extend the settings UI with your own React components.'
menu_order: 10
---

# Custom Field Types

MilliBase ships with 9 built-in field types. You can register additional types from your plugin's JavaScript.

## Registering a Custom Field Type

Use `window.MilliBase.registerFieldType()` to register a React component for a custom type:

```javascript
window.MilliBase.registerFieldType('my-range', function ({ field, value, onChange, disabled }) {
    return React.createElement('div', null,
        React.createElement('label', null, field.label),
        React.createElement('input', {
            type: 'range',
            min: field.min || 0,
            max: field.max || 100,
            value: value ?? field.default ?? 50,
            disabled: disabled,
            onChange: (e) => onChange(Number(e.target.value)),
        })
    );
});
```

Or with JSX if your build supports it:

```jsx
window.MilliBase.registerFieldType('my-range', ({ field, value, onChange, disabled }) => (
    <div>
        <label>{field.label}</label>
        <input
            type="range"
            min={field.min || 0}
            max={field.max || 100}
            value={value ?? field.default ?? 50}
            disabled={disabled}
            onChange={(e) => onChange(Number(e.target.value))}
        />
    </div>
));
```

## Component Props

Every custom field component receives these props:

| Prop | Type | Description |
|------|------|-------------|
| `field` | `object` | The field definition from the schema (all client-safe properties) |
| `value` | `mixed` | Current field value |
| `onChange` | `function` | Call with the new value to update state |
| `disabled` | `bool` | `true` when the field is overridden by a constant |

The `field` object contains all [client-safe properties](../02-usage/02-schema-definition.md#common-properties): `key`, `type`, `label`, `default`, `tooltip`, `placeholder`, `min`, `max`, `options`, `units`, `store`, etc.

## Using in the Schema

Reference your custom type in the PHP schema like any built-in type:

```php
'fields' => [
    [
        'key'     => 'general.opacity',
        'type'    => 'my-range',
        'label'   => 'Opacity',
        'default' => 80,
        'min'     => 0,
        'max'     => 100,
    ],
],
```

## Using MilliBase Components

MilliBase exposes the `LabelWithTooltip` component for consistent tooltip rendering:

```javascript
const { LabelWithTooltip } = window.MilliBase.components;

window.MilliBase.registerFieldType('my-field', ({ field, value, onChange, disabled }) => {
    const label = field.tooltip
        ? React.createElement(LabelWithTooltip, { label: field.label, tooltip: field.tooltip })
        : field.label;

    return React.createElement('div', null,
        React.createElement('label', null, label),
        // ... your input
    );
});
```

## Enqueue Order

Register your field type **after** the `millibase` script is loaded. Use `wp_add_inline_script` with the `'after'` position, or enqueue your script with `millibase` as a dependency:

```php
wp_enqueue_script(
    'my-custom-fields',
    plugins_url('assets/js/custom-fields.js', __FILE__),
    ['millibase'],
    '1.0.0',
    ['in_footer' => true]
);
```

> [!IMPORTANT]
> Custom field type registrations must happen before the React app mounts (DOM ready). Enqueue your script with `millibase` as a dependency and the registration will run before the auto-mount.

## Next Steps

- **[Custom Tab Components](./02-custom-tab-components.md)** — render fully custom tab content
- **[Field Types Reference](../04-reference/01-field-types.md)** — all built-in field types
