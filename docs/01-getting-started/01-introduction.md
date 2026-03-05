---
title: 'Introduction'
post_excerpt: 'What MilliBase does, how the architecture works, and what you need before installing.'
menu_order: 10
---

# Introduction

MilliBase is a declarative WordPress settings framework. Define your settings page in PHP arrays — tabs, sections, fields, validation — and MilliBase generates a React-powered admin UI automatically. It handles the full lifecycle: schema definition, storage with encryption, REST API integration, backup/restore, config file sync, and constant overrides.

## What MilliBase Provides

- **Declarative schema** — define tabs, sections, and fields in a PHP array
- **React admin UI** — generated from the schema using `@wordpress/components`
- **9 built-in field types** — text, number, password, toggle, select, unit, token-list, color, code
- **Custom extensibility** — register custom field types and custom tab components
- **Settings storage** — with dot-notation access, encryption, and constants override
- **Config file sync** — write settings to PHP files for pre-WordPress access
- **REST API** — save, reset, restore, and custom actions via WordPress REST
- **Conditional display** — show/hide fields based on other settings values
- **Tab override system** — add-on plugins can extend or replace tabs and sections

## Architecture Overview

MilliBase consists of five core classes wired together by a single facade:

```
Settings (facade)
├── Schema        — parses the config array into defaults, JSON schema, client config
├── Store         — CRUD, dot-notation access, encryption, constants, config file sync
├── AdminPage     — admin menu registration, JS/CSS enqueuing
└── RestController — REST endpoints for actions and status
```

The `Settings` class is the only entry point. It creates all internal components, registers WordPress hooks, and exposes the `Store` and `Schema` for programmatic access.

## How It Works

1. Your plugin defines a configuration array with tabs, sections, and fields
2. `Settings` creates a `Schema` that extracts defaults and builds a JSON schema
3. `Settings` creates a `Store` that manages persistence (DB, constants, config files)
4. On the admin page, the pre-built React bundle reads the schema and renders the UI
5. The React app communicates with WordPress via `POST /wp/v2/settings` (save) and custom REST endpoints (reset, restore, status)

## Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP         | >= 7.4  |
| WordPress   | >= 6.6  |

MilliBase is a Composer library — it is not a standalone WordPress plugin. It is meant to be included as a dependency in your own plugin.

## Next Steps

- **[Installation](./02-installation.md)** — install via Composer and set up your first settings page
- **[Configuration](../02-usage/01-configuration.md)** — full reference of configuration options
- **[Schema Definition](../02-usage/02-schema-definition.md)** — define tabs, sections, and fields

---

**Ready to get started?** Continue to the [Installation guide](./02-installation.md).
