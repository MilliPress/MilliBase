/**
 * Top-level SettingsApp: loading, error, tabs.
 * Reads config from props (injected by the auto-mount in millibase.js).
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Animate,
	TabPanel,
	Button,
} from '@wordpress/components';
import { caution, backup } from '@wordpress/icons';
import { useSettings } from './SettingsProvider.jsx';
import Banner from './Banner.jsx';
import Header from './Header.jsx';
import FooterRenderer from './FooterRenderer.jsx';
import TabRenderer from './TabRenderer.jsx';

const RecoveryDisplay = ( {
	error,
	onReset,
	onReload,
	isResetting,
	isRetrying,
} ) => (
	<Banner
		icon={ backup }
		iconColor="#dba617"
		title={ __( 'Settings could not be loaded', 'millibase' ) }
		message={ error }
		actions={
			<>
				<Button
					variant="primary"
					onClick={ onReset }
					isBusy={ isResetting }
					disabled={ isResetting || isRetrying }
				>
					{ isResetting
						? __( 'Resetting...', 'millibase' )
						: __( 'Reset to defaults', 'millibase' ) }
				</Button>
				<Button
					variant="secondary"
					onClick={ onReload }
					isBusy={ isRetrying }
					disabled={ isResetting || isRetrying }
				>
					{ isRetrying
						? __( 'Reloading...', 'millibase' )
						: __( 'Reload', 'millibase' ) }
				</Button>
			</>
		}
		footer={
			<p style={ { margin: '0' } }>
				{ __(
					'Resetting will replace the stored settings with the defaults. Your current settings — including the corrupt value — will be backed up automatically and can be restored later from the header menu.',
					'millibase'
				) }
			</p>
		}
	/>
);

const ErrorDisplay = ( { error, onRetry, isRetrying, troubleshooting } ) => (
	<Banner
		icon={ caution }
		iconColor="#dc3232"
		title={ __( 'Connection Error', 'millibase' ) }
		message={ error }
		actions={
			<Button
				variant="primary"
				onClick={ onRetry }
				isBusy={ isRetrying }
				disabled={ isRetrying }
			>
				{ isRetrying
					? __( 'Retrying...', 'millibase' )
					: __( 'Try Again', 'millibase' ) }
			</Button>
		}
		footer={ troubleshooting?.url && (
			<>
				<p style={ { margin: '0 0 12px 0' } }>
					{ troubleshooting.text ||
						__( 'Need help fixing this issue?', 'millibase' ) }
				</p>
				<Button
					href={ troubleshooting.url }
					target="_blank"
					variant="tertiary"
					size="compact"
					style={ { margin: '0' } }
				>
					{ troubleshooting.label ||
						__( 'View Troubleshooting Guide', 'millibase' ) }
					{ ' →' }
				</Button>
			</>
		) }
	/>
);

const SettingsApp = ( { config } ) => {
	const {
		error,
		schemaError,
		isLoadingSettings,
		activeTab,
		setActiveTab,
		retryConnection,
		isRetrying,
		triggerAction,
	} = useSettings();

	const [ isResetting, setIsResetting ] = useState( false );

	const handleReset = async () => {
		setIsResetting( true );
		try {
			await triggerAction( '__reset' );
		} catch ( e ) {
			// triggerAction surfaces errors via snackbar; nothing else to do.
		} finally {
			setIsResetting( false );
		}
	};

	const tabs = ( config.schema?.tabs || [] ).map( ( tab ) => ( {
		name: tab.name,
		title: tab.title,
		...tab,
	} ) );

	// Set initial tab if not already set.
	const initialTab = activeTab || ( tabs[ 0 ]?.name ?? 'settings' );

	return (
		<div className="millibase-settings-wrapper">
			<Header />
			<FooterRenderer />

			{ ( () => {
					if ( isLoadingSettings ) {
						return (
							<Animate
								type="slide-in"
								options={ { origin: 'top center' } }
							>
								{ ( { } ) => (
									<div className="millibase-loading-container">
										<p
											style={ {
												textAlign: 'center',
												margin: '0',
												padding: '15px 20px',
												borderBottom: '1px solid #e0e0e0',
												fontWeight: '500',
											} }
										>
											{ __(
												'Loading settings...',
												'millibase'
											) }
										</p>
									</div>
								) }
							</Animate>
						);
					}

					if ( schemaError ) {
						return (
							<RecoveryDisplay
								error={ schemaError }
								onReset={ handleReset }
								onReload={ retryConnection }
								isResetting={ isResetting }
								isRetrying={ isRetrying }
							/>
						);
					}

					if ( error ) {
						return (
							<ErrorDisplay
								error={ error }
								onRetry={ retryConnection }
								isRetrying={ isRetrying }
								troubleshooting={
									config.troubleshooting
								}
							/>
						);
					}

					if ( tabs.length === 0 ) {
						return null;
					}

					return (
						<Animate
							type="slide-in"
							options={ { origin: 'top' } }
						>
							{ ( { className } ) => (
								<TabPanel
									// TabPanel reads `initialTabName` only on
									// mount. Keying on the active tab remounts
									// it so programmatic setActiveTab()/hash
									// navigation actually switches the view.
									key={ initialTab }
									className={ `millibase-tabs ${ className }` }
									style={ {
										border: '1px solid #ddd',
										marginLeft: '-1px',
										marginRight: '-1px',
									} }
									initialTabName={ initialTab }
									onSelect={ ( tabName ) => {
										setActiveTab( tabName );
										window.scrollTo( {
											top: 0,
											behavior: 'instant',
										} );
									} }
									tabs={ tabs }
								>
									{ ( tab ) => (
										<div className="millibase-tab-content">
											<TabRenderer tab={ tab } />
										</div>
									) }
								</TabPanel>
							) }
						</Animate>
					);
				} )() }
		</div>
	);
};

export default SettingsApp;
