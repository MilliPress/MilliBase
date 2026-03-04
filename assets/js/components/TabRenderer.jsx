/**
 * Renders tab content from schema.
 * Supports sections with fields, and custom component tabs.
 */

import { createElement } from '@wordpress/element';
import { Panel } from '@wordpress/components';
import SectionRenderer from './SectionRenderer.jsx';
import { useSettings } from './SettingsProvider.jsx';

const TabRenderer = ( { tab } ) => {
	const context = useSettings();

	// Custom component tab.
	if ( tab.type === 'custom' && tab.component ) {
		const CustomComponent =
			window.MilliBase?.customComponents?.[ tab.component ];
		if ( CustomComponent ) {
			return createElement( CustomComponent, {
				status: context.status,
				settings: context.settings,
				triggerAction: context.triggerAction,
				isLoading: context.isLoading,
			} );
		}
		return null;
	}

	// Standard sections tab.
	if ( tab.sections ) {
		return (
			<Panel>
				{ tab.sections.map( ( section ) => (
					<SectionRenderer key={ section.id } section={ section } />
				) ) }
			</Panel>
		);
	}

	return null;
};

export default TabRenderer;
