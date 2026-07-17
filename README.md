# MilliBase

Declarative WordPress settings framework. Define your settings page in PHP arrays — tabs, sections, fields, validation — and get a React-powered admin UI automatically.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | >= 7.4  |
| WordPress   | >= 6.0  |

## Quick Start

> **⚠ Plugin authors: prefix MilliBase before shipping.**
> If you bundle MilliBase inside a distributed plugin, you **must** vendor-prefix
> it with [Strauss](https://github.com/BrianHenryIE/strauss) or php-scoper.
> Two plugins shipping the unprefixed `MilliBase\` namespace will collide at
> runtime — only one copy will load, and the other plugin runs against a
> version it wasn't tested with. See
> [Namespace Prefixing](docs/04-reference/04-namespace-prefixing.md) for setup
> and the rationale behind MilliBase's cross-prefix-tolerant typing.

```bash
composer require millipress/millibase
```

```php
use MilliBase\Manager;

$manager = new Manager(
    slug: 'my-plugin',
    config: fn() => [
        'page_title' => __( 'My Plugin', 'my-plugin' ),
        'menu_title' => __( 'My Plugin', 'my-plugin' ),
        'header'     => [ 'title' => __( 'My Plugin Settings', 'my-plugin' ) ],
        'tabs'       => [
            [
                'name'     => 'general',
                'title'    => __( 'General', 'my-plugin' ),
                'sections' => [
                    [
                        'id'     => 'main',
                        'title'  => __( 'Main Settings', 'my-plugin' ),
                        'fields' => [
                            [
                                'key'     => 'general.enabled',
                                'type'    => 'toggle',
                                'label'   => __( 'Enable Feature', 'my-plugin' ),
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
);

// Programmatic access:
$manager->settings()->get('general.enabled'); // true
```

## Features

- **Declarative schema** — define tabs, sections, and fields in PHP arrays
- **React admin UI** — auto-generated from the schema using `@wordpress/components`
- **9 built-in field types** — text, number, password, toggle, select, unit, token-list, color, code
- **Custom extensibility** — register custom field types and custom tab components from JS
- **Provided components** — reusable React UI on `window.MilliBase.components` (`InfoPopover`, `LabelWithTooltip`) for custom tabs
- **Conditional display** — show/hide fields based on other settings values
- **Settings priority** — constants > config file > database > defaults
- **Encryption** — automatic sodium encryption for sensitive fields (keys prefixed with `enc_`)
- **Config file sync** — write settings to PHP files for pre-WordPress access (blog-aware per-operation; `_network-<id>.php` mirror for network-mode)
- **Network mode** — `'network' => true` routes storage to `wp_sitemeta` and the admin page to Network Admin
- **Migrations** — declarative `name@version` one-shot runner with per-scope state (site / network)
- **Backup & restore** — transient-based backup with 12-hour expiry
- **Import / export** — settings serialization with encryption handling
- **Tab overrides** — add-on plugins can extend or replace tabs and sections via filters
- **REST API** — namespaced `GET/POST /{namespace}/v1/settings` + custom action endpoints
- **WP-CLI** — auto-registered `wp <slug> config` commands; two Managers sharing a `cli.slug` auto-merge into one tree
- **Logging** — channel-prefixed `Logger` with level gating (`error`/`warning`/`debug`) and a shared `millibase_log` action; safe before WordPress loads

## Documentation

Full documentation is in the [`docs/`](docs/) directory:

- [Introduction](docs/01-getting-started/01-introduction.md)
- [Installation](docs/01-getting-started/02-installation.md)
- [Configuration](docs/02-usage/01-configuration.md)
- [Schema Definition](docs/02-usage/02-schema-definition.md)
- [Programmatic Access](docs/02-usage/03-programmatic-access.md)
- [WP-CLI Commands](docs/02-usage/04-wp-cli.md)
- [Migrations](docs/02-usage/05-migrations.md)
- [Network Settings](docs/02-usage/06-network-settings.md)
- [Custom Field Types](docs/03-customization/01-custom-field-types.md)
- [Custom Tab Components](docs/03-customization/02-custom-tab-components.md)
- [Extending with Filters](docs/03-customization/03-extending-with-filters.md)
- [Reference: Field Types](docs/04-reference/01-field-types.md) · [Settings API](docs/04-reference/02-settings-api.md) · [Hooks & Filters](docs/04-reference/03-hooks-and-filters.md) · [Namespace Prefixing](docs/04-reference/04-namespace-prefixing.md)

## License

GPL-2.0-or-later
