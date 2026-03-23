/**
 * Top-level SettingsApp: loading, error, tabs.
 * Reads config from props (injected by the auto-mount in millibase.js).
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useCallback } from '@wordpress/element';
import {
	Animate,
	TabPanel,
	Button,
	Icon,
} from '@wordpress/components';
import { caution } from '@wordpress/icons';
import { useSettings } from './SettingsProvider.jsx';
import Header from './Header.jsx';
import TabRenderer from './TabRenderer.jsx';

/**
 * Sticky-nav state machine (all DOM-driven for per-pixel performance).
 *
 * Phases:
 *   idle        – tabs in normal document flow, no stickiness.
 *   sticky      – scrolling up past natural position → tabs stick below header.
 *   sliding-out – scrolling down while sticky → tabs shift up pixel-by-pixel.
 */
const useStickyNav = ( wrapperRef ) => {
	const lastScrollY = useRef( window.scrollY );
	const tabsNaturalTop = useRef( 0 );
	const phase = useRef( 'idle' );
	const slideOffset = useRef( 0 );

	const updateCssVars = useCallback( () => {
		const el = wrapperRef.current;
		if ( ! el ) return;

		const adminBar = document.getElementById( 'wpadminbar' );
		const adminBarH = adminBar
			? adminBar.getBoundingClientRect().height
			: 0;
		const header = el.querySelector( '.millibase-header' );
		const headerH = header ? header.getBoundingClientRect().height : 0;

		const page = el.closest( '.millibase-page' );
		if ( page ) {
			page.style.setProperty(
				'--millibase-adminbar-h',
				`${ adminBarH }px`
			);
			page.style.setProperty(
				'--millibase-tabs-top',
				`${ adminBarH + headerH }px`
			);
		}

		// Record the natural document-offset of the tab bar.
		const tabBar = el.querySelector( '.components-tab-panel__tabs' );
		if ( tabBar ) {
			tabsNaturalTop.current = tabBar.offsetTop + el.offsetTop;
		}
	}, [ wrapperRef ] );

	/** Reset to idle: remove class, clear inline transform. */
	const resetToIdle = ( tabPanel, tabBar ) => {
		phase.current = 'idle';
		slideOffset.current = 0;
		tabPanel.classList.remove( 'is-tabs-sticky' );
		tabBar.style.transform = '';
	};

	useEffect( () => {
		updateCssVars();
		window.addEventListener( 'resize', updateCssVars );

		const onScroll = () => {
			const el = wrapperRef.current;
			if ( ! el ) return;

			const currentY = window.scrollY;
			const delta = currentY - lastScrollY.current;
			lastScrollY.current = currentY;

			if ( Math.abs( delta ) < 1 ) return;

			const scrollingUp = delta < 0;
			const pastTabs = currentY > tabsNaturalTop.current;
			const tabPanel = el.querySelector( '.millibase-tabs' );
			const tabBar = el.querySelector(
				'.components-tab-panel__tabs'
			);
			if ( ! tabPanel || ! tabBar ) return;

			const tabBarH = tabBar.getBoundingClientRect().height;

			// Scrolled back to the natural tab position — always reset.
			if ( ! pastTabs ) {
				if ( phase.current !== 'idle' ) {
					resetToIdle( tabPanel, tabBar );
				}
				return;
			}

			if ( phase.current === 'idle' && scrollingUp ) {
				// Start sliding in from fully hidden.
				phase.current = 'sliding';
				slideOffset.current = tabBarH;
				tabPanel.classList.add( 'is-tabs-sticky' );
			}

			if ( phase.current === 'sliding' ) {
				// Adjust offset: scrolling up decreases it, down increases it.
				slideOffset.current = Math.min(
					tabBarH,
					Math.max( 0, slideOffset.current + delta )
				);

				if ( slideOffset.current >= tabBarH ) {
					// Fully hidden — back to idle.
					resetToIdle( tabPanel, tabBar );
				} else if ( slideOffset.current <= 0 ) {
					// Fully visible — lock in sticky.
					phase.current = 'sticky';
					tabBar.style.transform = '';
				} else {
					tabBar.style.transform = `translateY(${ -slideOffset.current }px)`;
				}
			} else if ( phase.current === 'sticky' && ! scrollingUp ) {
				// Start sliding back out.
				phase.current = 'sliding';
				slideOffset.current = delta;
				tabBar.style.transform = `translateY(${ -slideOffset.current }px)`;
			}
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );

		return () => {
			window.removeEventListener( 'resize', updateCssVars );
			window.removeEventListener( 'scroll', onScroll );
		};
	}, [ updateCssVars ] );
};

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

	const wrapperRef = useRef( null );
	useStickyNav( wrapperRef );

	const tabs = ( config.schema?.tabs || [] ).map( ( tab ) => ( {
		name: tab.name,
		title: tab.title,
		...tab,
	} ) );

	// Set initial tab if not already set.
	const initialTab = activeTab || ( tabs[ 0 ]?.name ?? 'settings' );

	return (
		<div className="millibase-settings-wrapper" ref={ wrapperRef }>
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
