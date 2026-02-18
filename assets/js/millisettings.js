/**
 * MilliSettings — Entry point + global init function.
 *
 * This file is the webpack entry point. It:
 * 1. Defines the global MilliSettings registry (init, registerComponent, registerFieldType)
 * 2. On DOM ready, auto-mounts a SettingsApp into each container with data-slug
 */

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import SettingsApp from './components/SettingsApp.jsx';
import { SnackbarProvider } from './components/SnackbarProvider.jsx';
import { SettingsProvider } from './components/SettingsProvider.jsx';
import { LabelWithTooltip } from './components/LabelWithTooltip.jsx';

import '../css/millisettings.scss';

// ─── Global registry ────────────────────────────────────────────────

window.MilliSettings = window.MilliSettings || {};

// Configs store: { slug: configObject }
window.MilliSettings.configs = window.MilliSettings.configs || {};

// Custom components store: { name: Component }
window.MilliSettings.customComponents = window.MilliSettings.customComponents || {};

// Custom field types store: { type: Component }
window.MilliSettings.customFieldTypes = window.MilliSettings.customFieldTypes || {};

/**
 * Register a config for a slug. Called by wp_add_inline_script() from PHP.
 */
window.MilliSettings.init = window.MilliSettings.init || function ( slug, config ) {
	window.MilliSettings.configs[ slug ] = config;
};

/**
 * Register a custom component (e.g., for custom tab content).
 *
 * @param {string}   name      The component name (referenced in schema as `component`).
 * @param {Function} component A React component or function component.
 */
window.MilliSettings.registerComponent = function ( name, component ) {
	window.MilliSettings.customComponents[ name ] = component;
};

/**
 * Register a custom field type.
 *
 * @param {string}   type      The field type string (used in schema `field.type`).
 * @param {Function} component A React component: receives { field, value, onChange, disabled }.
 */
window.MilliSettings.registerFieldType = function ( type, component ) {
	window.MilliSettings.customFieldTypes[ type ] = component;
};

// ─── Exposed components for custom tab authors ──────────────────────

window.MilliSettings.components = {
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
		const config = window.MilliSettings.configs[ slug ];

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
