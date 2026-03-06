/**
 * Top-level SettingsApp: loading, error, tabs.
 * Reads config from props (injected by the auto-mount in millibase.js).
 */

import { __ } from '@wordpress/i18n';
import {
	Animate,
	TabPanel,
	Button,
	ExternalLink,
	Icon,
} from '@wordpress/components';
import { caution } from '@wordpress/icons';
import { useSettings } from './SettingsProvider.jsx';
import Header from './Header.jsx';
import TabRenderer from './TabRenderer.jsx';

const ErrorDisplay = ( { error, onRetry, isRetrying, troubleshooting } ) => (
	<div
		className="millibase-error-container"
		style={ {
			padding: '60px 20px',
			textAlign: 'center',
			maxWidth: '600px',
			margin: '0 auto',
		} }
	>
		<div style={ { marginBottom: '24px' } }>
			<Icon
				icon={ caution }
				size={ 48 }
				style={ { color: '#dc3232', opacity: 0.8 } }
			/>
		</div>
		<h2
			style={ {
				margin: '0 0 16px 0',
				fontSize: '24px',
				fontWeight: '600',
				color: '#1e1e1e',
			} }
		>
			{ __( 'Connection Error', 'millibase' ) }
		</h2>
		<p
			style={ {
				fontSize: '16px',
				lineHeight: '1.5',
				color: '#646970',
				maxWidth: '500px',
				margin: '0 auto 32px auto',
			} }
		>
			{ error }
		</p>
		<div
			style={ {
				marginBottom: '32px',
				display: 'flex',
				justifyContent: 'center',
				gap: '12px',
				flexWrap: 'wrap',
			} }
		>
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
		</div>
		{ troubleshooting?.url && (
			<div
				style={ {
					borderTop: '1px solid #e0e0e0',
					paddingTop: '24px',
					color: '#646970',
					fontSize: '14px',
				} }
			>
				<p style={ { margin: '0 0 12px 0' } }>
					{ troubleshooting.text ||
						__( 'Need help fixing this issue?', 'millibase' ) }
				</p>
				<ExternalLink href={ troubleshooting.url }>
					{ troubleshooting.label ||
						__( 'View Troubleshooting Guide', 'millibase' ) }
					{ ' →' }
				</ExternalLink>
			</div>
		) }
	</div>
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
									config.header?.troubleshooting
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
									onSelect={ ( tabName ) =>
										setActiveTab( tabName )
									}
									tabs={ tabs }
								>
									{ ( tab ) => (
										<div
											className="millibase-tab-content"
											style={ { margin: '-1px' } }
										>
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
