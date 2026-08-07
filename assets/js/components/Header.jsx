/**
 * Header component: title, links, save button, custom buttons, actions dropdown, progress bar.
 * Fully driven by the PHP schema's `header` config.
 */

import { __ } from '@wordpress/i18n';
import { createElement, useEffect, useRef, useState } from '@wordpress/element';
import {
	Animate,
	Button,
	Dropdown,
	Flex,
	FlexItem,
	MenuGroup,
	MenuItem,
	PanelBody,
	ExternalLink,
	ProgressBar,
} from '@wordpress/components';
import * as wpIcons from '@wordpress/icons';
import { useSettings } from './SettingsProvider.jsx';
import evaluateCondition, { resolveDotPath } from '../utils/evaluateCondition.js';
import resolveCustomComponent from '../utils/resolveCustomComponent.js';

const Header = () => {
	const {
		config,
		settings,
		status,
		saveSettings,
		isSaving,
		isLoading,
		hasChanges,
		triggerAction,
		activeTab,
	} = useSettings();

	const header = config.header || {};
	const links = header.links || [];
	const buttons = header.buttons || [];
	const menuItems = header.menu_items || [];

	// Track which custom button modals are open.
	const [ openModals, setOpenModals ] = useState( {} );

	// Custom-component tabs have nothing to save; hide the save button
	// there unless another tab's edits are still unsaved.
	const tabs = config.schema?.tabs || [];
	const currentTab =
		tabs.find( ( tab ) => tab.name === activeTab ) || tabs[ 0 ];
	const showSaveButton =
		! currentTab || !! currentTab.sections?.length || hasChanges;

	// Core's notice relocation runs on DOM-ready, before the wp-header-end
	// marker exists; pull stray admin notices in ourselves once mounted.
	const noticesRef = useRef( null );

	useEffect( () => {
		const container = noticesRef.current;
		if ( ! container ) {
			return;
		}

		document
			.querySelectorAll(
				'#wpbody-content div.notice, #wpbody-content div.updated, #wpbody-content div.error'
			)
			.forEach( ( notice ) => {
				if (
					notice.classList.contains( 'inline' ) ||
					notice.classList.contains( 'below-h2' ) ||
					container.contains( notice )
				) {
					return;
				}
				container.appendChild( notice );
			} );
	}, [] );

	const renderCustomButton = ( btn, idx ) => {
		// If button has a registered component, render it.
		if ( btn.component ) {
			const CustomBtn = resolveCustomComponent( btn.component );
			if ( CustomBtn ) {
				return createElement( CustomBtn, {
					key: idx,
					status,
					triggerAction,
					isSaving,
					isLoading,
				} );
			}
		}

		return (
			<Button
				key={ idx }
				__next40pxDefaultSize
				variant={ btn.variant || 'secondary' }
				onClick={ () => {
					if ( btn.action ) {
						triggerAction( btn.action );
					}
				} }
				disabled={ isSaving || isLoading }
			>
				{ btn.label }
			</Button>
		);
	};

	return (
		<>
			<PanelBody className="millibase-header">
				<hr className="wp-header-end" />
				{ /* Non-React container for relocated notices. */ }
				<div
					className="millibase-header-notices"
					ref={ noticesRef }
				/>
				<Flex align="center">
					<FlexItem>
						<h1 style={ { padding: '0' } }>
							{ header.title || '' }
						</h1>

						{ links.length > 0 && (
							<Flex expanded="false" justify="start">
								{ links.map( ( link, i ) => (
									<FlexItem key={ i }>
										<ExternalLink
											className="external-link"
											href={ link.url }
										>
											{ link.label }
										</ExternalLink>
									</FlexItem>
								) ) }
							</Flex>
						) }
					</FlexItem>
					<FlexItem align="end">
						{ showSaveButton && (
							<Button
								__next40pxDefaultSize
								style={ { marginRight: '10px' } }
								isBusy={ isSaving }
								isPrimary
								onClick={ saveSettings }
								disabled={ ! hasChanges || isSaving }
							>
								{ isSaving
									? __( 'Saving…', 'millibase' )
									: __( 'Save Settings', 'millibase' ) }
							</Button>
						) }

						{ /* Custom buttons from header config */ }
						{ buttons.map( ( btn, idx ) =>
							renderCustomButton( btn, idx )
						) }

						{ /* Actions dropdown */ }
						<Dropdown
							className="millibase-actions-dropdown"
							contentClassName="millibase-actions-dropdown-content"
							popoverProps={ { placement: 'bottom-end' } }
							renderToggle={ ( { isOpen, onToggle } ) => (
								<Button
									__next40pxDefaultSize
									icon={ wpIcons.moreVertical }
									label={ __( 'More Actions', 'millibase' ) }
									disabled={ isSaving || isLoading }
									onClick={ onToggle }
									aria-expanded={ isOpen }
								/>
							) }
							renderContent={ ( { onClose } ) => {
								// Menu entries carry a position (lower = higher up).
								// Custom items default to 10; the built-in
								// last-resort actions sit at 100.
								const entries = [];

								// Custom menu items (filtered by condition).
								menuItems.filter( ( item ) => {
									if ( ! item.condition ) {
										return true;
									}
									if ( typeof item.condition === 'string' ) {
										return !! resolveDotPath( settings, item.condition );
									}
									return evaluateCondition( item.condition, settings );
								} ).forEach( ( item, idx ) => {
									entries.push( {
										position:
											typeof item.position === 'number'
												? item.position
												: 10,
										element: (
											<MenuItem
												key={ `custom-${ idx }` }
												__next40pxDefaultSize
												icon={
													wpIcons[ item.icon ] ||
													null
												}
												iconPosition="left"
												onClick={ () => {
													onClose();
													if ( item.url ) {
														window.open(
															item.url,
															'_blank'
														);
													} else if ( item.action ) {
														triggerAction(
															item.action
														);
													}
												} }
											>
												{ item.label }
											</MenuItem>
										),
									} );
								} );

								// Built-in: Reset.
								entries.push( {
									position: 100,
									element: (
										<MenuItem
											key="reset"
											__next40pxDefaultSize
											icon={ wpIcons.flipVertical }
											iconPosition="left"
											onClick={ () => {
												onClose();
												triggerAction( '__reset' );
											} }
											disabled={
												status.settings?.has_defaults
											}
										>
											{ __(
												'Reset all Settings',
												'millibase'
											) }
										</MenuItem>
									),
								} );

								// Built-in: Restore (shown conditionally).
								if (
									status.settings?.has_backup &&
									status.settings?.has_defaults
								) {
									entries.push( {
										position: 100,
										element: (
											<MenuItem
												key="restore"
												__next40pxDefaultSize
												icon={ wpIcons.backup }
												iconPosition="left"
												onClick={ () => {
													onClose();
													triggerAction( '__restore' );
												} }
											>
												{ __(
													'Restore previous Settings',
													'millibase'
												) }
											</MenuItem>
										),
									} );
								}

								entries.sort(
									( a, b ) => a.position - b.position
								);

								return (
									<MenuGroup
										label={ __(
											'More Actions',
											'millibase'
										) }
									>
										{ entries.map( ( e ) => e.element ) }
									</MenuGroup>
								);
							} }
						/>
					</FlexItem>
				</Flex>
			</PanelBody>

			{ ( isLoading || isSaving ) && (
				<Animate type="slide-in" options={ { origin: 'top center' } }>
					{ ( { className } ) => (
						<ProgressBar
							className={ `millibase-progress ${ className }` }
						/>
					) }
				</Animate>
			) }
		</>
	);
};

export default Header;
