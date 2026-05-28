/**
 * Hydrates the admin footer's left/right slots into registered React
 * components when the schema's `footer` config used the array form
 * `['component' => 'Name']`.
 *
 * The PHP `update_footer` / `admin_footer_text` filters emit placeholder
 * elements like `<span class="millibase-footer-slot" data-component="Name">`;
 * this component finds them and uses React portals so each registered
 * component renders into its placeholder while remaining inside the
 * SettingsApp's React tree (and thus has access to `useSettings()` state).
 *
 * Components receive the standard prop set custom tabs already get:
 * `{ status, settings, triggerAction, isLoading }`. Placeholders whose
 * `data-component` isn't found in `window.MilliBase.customComponents`
 * are skipped silently — degrades to a literally empty span.
 */

import { createElement, createPortal, useEffect, useState } from '@wordpress/element';
import { useSettings } from './SettingsProvider.jsx';

const FooterRenderer = () => {
	const { status, settings, triggerAction, isLoading } = useSettings();
	const [ slots, setSlots ] = useState( [] );

	useEffect( () => {
		setSlots(
			Array.from(
				document.querySelectorAll(
					'.millibase-footer-slot[data-component]'
				)
			)
		);
	}, [] );

	if ( slots.length === 0 ) {
		return null;
	}

	return slots.map( ( el, i ) => {
		const componentName = el.getAttribute( 'data-component' );
		if ( ! componentName ) {
			return null;
		}
		const Component =
			window.MilliBase?.customComponents?.[ componentName ];
		if ( ! Component ) {
			return null;
		}
		return createPortal(
			createElement( Component, {
				status,
				settings,
				triggerAction,
				isLoading,
			} ),
			el,
			`${ componentName }-${ i }`
		);
	} );
};

export default FooterRenderer;
