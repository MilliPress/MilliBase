/**
 * Header component: title, links, save button, custom buttons, actions dropdown, progress bar.
 * Fully driven by the PHP schema's `header` config.
 */

import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
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
	} = useSettings();

	const header = config.header || {};
	const links = header.links || [];
	const buttons = header.buttons || [];
	const menuItems = header.menu_items || [];

	// Track which custom button modals are open.
	const [ openModals, setOpenModals ] = useState( {} );

	const renderCustomButton = ( btn, idx ) => {
		// If button has a registered component, render it.
		if ( btn.component ) {
			const CustomBtn =
				window.MilliBase?.customComponents?.[ btn.component ];
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
						{ /* Save button — always present */ }
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
							renderContent={ ( { onClose } ) => (
								<MenuGroup
									label={ __( 'More Actions', 'millibase' ) }
								>
									{ /* Custom menu items (filtered by condition) */ }
									{ menuItems.filter( ( item ) => {
										if ( ! item.condition ) {
											return true;
										}
										if ( typeof item.condition === 'string' ) {
											return !! resolveDotPath( settings, item.condition );
										}
										return evaluateCondition( item.condition, settings );
									} ).map( ( item, idx ) => (
										<MenuItem
											key={ idx }
											__next40pxDefaultSize
											icon={
												wpIcons[ item.icon ] || null
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
									) ) }

									{ /* Built-in: Reset */ }
									<MenuItem
										__next40pxDefaultSize
										icon={ wpIcons.flipVertical }
										iconPosition="left"
										onClick={ () => {
											onClose();
											triggerAction( 'reset' );
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

									{ /* Built-in: Restore (shown conditionally) */ }
									{ status.settings?.has_backup &&
										status.settings?.has_defaults && (
											<MenuItem
												__next40pxDefaultSize
												icon={ wpIcons.backup }
												iconPosition="left"
												onClick={ () => {
													onClose();
													triggerAction( 'restore' );
												} }
											>
												{ __(
													'Restore previous Settings',
													'millibase'
												) }
											</MenuItem>
										) }
								</MenuGroup>
							) }
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
