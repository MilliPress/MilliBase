# MilliBase

Declarative WordPress settings framework. Define your settings page in PHP arrays — tabs, sections, fields, validation — and get a React-powered admin UI automatically.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | >= 7.4  |
| WordPress   | >= 6.0  |

## Quick Start

```bash
composer require millipress/millibase
```

```php
use MilliBase\Settings;

$settings = new Settings([
    'slug'           => 'my-plugin',
    'option_name'    => 'my_plugin_settings',
    'page_title'     => 'My Plugin',
    'menu_title'     => 'My Plugin',
    'rest_namespace' => 'my-plugin/v1',
    'basename'       => plugin_basename(__FILE__),
    'header'         => ['title' => 'My Plugin Settings'],
    'tabs'           => [
        [
            'name'     => 'general',
            'title'    => 'General',
            'sections' => [
                [
                    'id'     => 'main',
                    'title'  => 'Main Settings',
                    'fields' => [
                        [
                            'key'     => 'general.enabled',
                            'type'    => 'toggle',
                            'label'   => 'Enable Feature',
                            'default' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],
]);

// Programmatic access:
$settings->store()->get('general.enabled'); // true
```

## Features

- **Declarative schema** — define tabs, sections, and fields in PHP arrays
- **React admin UI** — auto-generated from the schema using `@wordpress/components`
- **9 built-in field types** — text, number, password, toggle, select, unit, token-list, color, code
- **Custom extensibility** — register custom field types and custom tab components from JS
- **Conditional display** — show/hide fields based on other settings values
- **Settings priority** — constants > config file > database > defaults
- **Encryption** — automatic sodium encryption for sensitive fields (keys prefixed with `enc_`)
- **Config file sync** — write settings to PHP files for pre-WordPress access
- **Backup & restore** — transient-based backup with 12-hour expiry
- **Import / export** — settings serialization with encryption handling
- **Tab overrides** — add-on plugins can extend or replace tabs and sections via filters
- **REST API** — save, reset, restore, status, and custom action endpoints

## Documentation

Full documentation is in the [`docs/`](docs/) directory:

- [Introduction](docs/01-getting-started/01-introduction.md)
- [Installation](docs/01-getting-started/02-installation.md)
- [Configuration](docs/02-usage/01-configuration.md)
- [Schema Definition](docs/02-usage/02-schema-definition.md)
- [Programmatic Access](docs/02-usage/03-programmatic-access.md)
- [Custom Field Types](docs/03-customization/01-custom-field-types.md)
- [Custom Tab Components](docs/03-customization/02-custom-tab-components.md)
- [Extending with Filters](docs/03-customization/03-extending-with-filters.md)
- [Reference: Field Types](docs/04-reference/01-field-types.md) · [Store API](docs/04-reference/02-store-api.md) · [Hooks & Filters](docs/04-reference/03-hooks-and-filters.md)

## License

GPL-2.0-or-later
