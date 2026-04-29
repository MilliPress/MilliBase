/**
 * Top-level SettingsApp: loading, error, tabs.
 * Reads config from props (injected by the auto-mount in millibase.js).
 */

import { __ } from '@wordpress/i18n';
import {
	Animate,
	TabPanel,
	Button,
} from '@wordpress/components';
import { caution } from '@wordpress/icons';
import { useSettings } from './SettingsProvider.jsx';
import Banner from './Banner.jsx';
import Header from './Header.jsx';
import TabRenderer from './TabRenderer.jsx';

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
		isLoading,
		activeTab,
		setActiveTab,
		retryConnection,
		isRetrying,
	} = useSettings();

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

			{ ( () => {
					if ( isLoading ) {
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
