/**
 * MilliBase — Entry point + global init function.
 *
 * This file is the webpack entry point. It:
 * 1. Defines the global MilliBase registry (init, registerComponent, registerFieldType)
 * 2. On DOM ready, auto-mounts a SettingsApp into each container with data-slug
 */

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import SettingsApp from './components/SettingsApp.jsx';
import { SnackbarProvider } from './components/SnackbarProvider.jsx';
import { SettingsProvider } from './components/SettingsProvider.jsx';
import { LabelWithTooltip } from './components/LabelWithTooltip.jsx';

import '../css/millibase.scss';

// ─── Global registry ────────────────────────────────────────────────

window.MilliBase = window.MilliBase || {};

// Configs store: { slug: configObject }
window.MilliBase.configs = window.MilliBase.configs || {};

// Custom components store: { name: Component }
window.MilliBase.customComponents = window.MilliBase.customComponents || {};

// Custom field types store: { type: Component }
window.MilliBase.customFieldTypes = window.MilliBase.customFieldTypes || {};

/**
 * Register a config for a slug. Called by wp_add_inline_script() from PHP.
 */
window.MilliBase.init = window.MilliBase.init || function ( slug, config ) {
	window.MilliBase.configs[ slug ] = config;
};

/**
 * Register a custom component (e.g., for custom tab content).
 *
 * @param {string}   name      The component name (referenced in schema as `component`).
 * @param {Function} component A React component or function component.
 */
window.MilliBase.registerComponent = function ( name, component ) {
	window.MilliBase.customComponents[ name ] = component;
};

/**
 * Register a custom field type.
 *
 * @param {string}   type      The field type string (used in schema `field.type`).
 * @param {Function} component A React component: receives { field, value, onChange, disabled }.
 */
window.MilliBase.registerFieldType = function ( type, component ) {
	window.MilliBase.customFieldTypes[ type ] = component;
};

// ─── Exposed components for custom tab authors ──────────────────────

window.MilliBase.components = {
	LabelWithTooltip,
};

// useSettings and useSnackbar are re-exported from the providers
// so custom components can import them:
export { useSettings } from './components/SettingsProvider.jsx';
export { useSnackbar } from './components/SnackbarProvider.jsx';

// ─── Auto-mount ─────────────────────────────────────────────────────

domReady( () => {
	// Find all containers with data-slug and mount a SettingsApp into each.
	const containers = document.querySelectorAll( '[data-slug]' );

	containers.forEach( ( container ) => {
		const slug = container.getAttribute( 'data-slug' );
		const config = window.MilliBase.configs[ slug ];

		if ( ! config ) {
			return;
		}

		createRoot( container ).render(
			<SnackbarProvider slug={ slug }>
				<SettingsProvider config={ config }>
					<SettingsApp config={ config } />
				</SettingsProvider>
			</SnackbarProvider>
		);
	} );
} );
