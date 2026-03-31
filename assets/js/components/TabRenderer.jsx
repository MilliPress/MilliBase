/**
 * Renders tab content from schema.
 * Supports sections with fields, custom component tabs, section groups,
 * and accordion behavior (per-group when groups exist).
 */

import { createElement, useState, useCallback } from '@wordpress/element';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import SectionRenderer from './SectionRenderer.jsx';
import { useSettings } from './SettingsProvider.jsx';

/**
 * Group consecutive sections by their `group` property.
 *
 * Returns an array of { label, sections } objects. Consecutive sections
 * with the same group label are combined. Ungrouped sections are collected
 * into a single entry (label = null) so accordion applies across all of them.
 */
const groupSections = ( sections ) => {
	const groups = [];
	let current = null;

	for ( const section of sections ) {
		const label = section.group || null;

		if ( current && current.label === label ) {
			current.sections.push( section );
		} else {
			current = { label, sections: [ section ] };
			groups.push( current );
		}
	}

	return groups;
};

/**
 * Renders a group of sections, each in its own Panel.
 * Manages accordion state when the tab opts in.
 */
const SectionGroup = ( { group, isAccordion } ) => {
	const [ openSectionId, setOpenSectionId ] = useState( null );

	const handleAccordionToggle = useCallback(
		( sectionId, isOpen ) => {
			setOpenSectionId( isOpen ? sectionId : null );
		},
		[]
	);

	return (
		<div className="millibase-section-group">
			{ group.label && (
				<h3 className="millibase-section-group__heading">
					{ group.label }
				</h3>
			) }
			{ group.sections.map( ( section ) => (
				<Panel key={ section.id }>
					<SectionRenderer
						section={ section }
						accordion={ isAccordion }
						accordionOpen={ openSectionId === section.id }
						onAccordionToggle={ handleAccordionToggle }
					/>
				</Panel>
			) ) }
		</div>
	);
};

const TabRenderer = ( { tab } ) => {
	const context = useSettings();
	const isAccordion = !! tab.accordion;

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
		const groups = groupSections( tab.sections );

		return (
			<div className="millibase-tab-content">
				{ tab.intro && ( () => {
					const CustomDesc =
						window.MilliBase?.customComponents?.[ tab.intro ];
					return CustomDesc
						? createElement( CustomDesc, context )
						: <Panel><PanelBody><PanelRow>{ tab.intro }</PanelRow></PanelBody></Panel>;
				} )() }
				{ groups.map( ( group, index ) => (
					<SectionGroup
						key={ group.label || `group-${ index }` }
						group={ group }
						isAccordion={ isAccordion }
					/>
				) ) }
			</div>
		);
	}

	return null;
};

export default TabRenderer;
