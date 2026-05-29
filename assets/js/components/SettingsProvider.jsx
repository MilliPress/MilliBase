import {
	createContext,
	useContext,
	useState,
	useEffect,
	useCallback,
	useMemo,
	useRef,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { stripTags } from '@wordpress/sanitize';
import { __ } from '@wordpress/i18n';
import { useSnackbar } from './SnackbarProvider.jsx';
import { runActionChain } from './actionChain.js';

const SettingsContext = createContext();

export const SettingsProvider = ( { config, children } ) => {
	const { restNamespace } = config;

	const [ status, setStatus ] = useState( {} );
	const [ settings, setSettings ] = useState( {} );
	const [ initialSettings, setInitialSettings ] = useState( {} );
	const [ isLoadingSettings, setIsLoadingSettings ] = useState( true );
	const [ actionLoadingCount, setActionLoadingCount ] = useState( 0 );
	const isLoadingAction = actionLoadingCount > 0;
	const [ isSaving, setIsSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ schemaError, setSchemaError ] = useState( null );
	const [ hasChanges, setHasChanges ] = useState( false );
	const [ hasStorageChanges, setHasStorageChanges ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( () => {
		const hash = window.location.hash.replace( '#', '' );
		return hash || null;
	} );
	const [ isRetrying, setIsRetrying ] = useState( false );

	const setActiveTabWithHash = useCallback( ( tabName ) => {
		setActiveTab( tabName );
		window.location.hash = tabName;
	}, [] );
	const statusIntervalRef = useRef( null );
	const errorRef = useRef( error );
	const initialSettingsRef = useRef( initialSettings );
	const settingsRef = useRef( settings );
	const statusRef = useRef( status );
	const lastStatusFingerprintRef = useRef( null );
	const hasChangesRef = useRef( hasChanges );
	const hasStorageChangesRef = useRef( hasStorageChanges );
	const { showSnackbar } = useSnackbar();
	const showSnackbarRef = useRef( showSnackbar );

	const delay = ( ms ) =>
		new Promise( ( resolve ) => setTimeout( resolve, ms ) );

	const handleApiError = useCallback( ( apiError ) => {
		let message = __( 'An unexpected error occurred.', 'millibase' );

		if ( apiError?.message ) {
			message = apiError.message;
		} else if ( apiError?.code ) {
			switch ( apiError.code ) {
				case 'rest_no_route':
					message = __( 'API endpoint not found.', 'millibase' );
					break;
				case 'rest_forbidden':
					message = __( 'Access denied.', 'millibase' );
					break;
				case 'rest_cookie_invalid_nonce':
					message = __(
						'Security check failed. Please refresh.',
						'millibase'
					);
					break;
				default:
					message =
						apiError.message ||
						__( 'API request failed.', 'millibase' );
			}
		}

		return typeof message === 'string' ? stripTags( message ) : message;
	}, [] );

	const apiRequest = useCallback(
		async ( options ) => {
			try {
				await delay( 300 );
				return await apiFetch( options );
			} catch ( apiError ) {
				const errorMessage = handleApiError( apiError );
				throw new Error( errorMessage );
			}
		},
		[ handleApiError ]
	);

	const fetchStatus = useCallback( async () => {
		try {
			const response = await apiRequest( {
				path: `/${ restNamespace }/status`,
				method: 'GET',
			} );
			setStatus( response );
			setError( null );
			return response;
		} catch ( fetchError ) {
			const errorMessage = fetchError.message;
			setStatus( { connected: false, error: errorMessage } );
			setError( errorMessage );
			return errorMessage;
		}
	}, [ apiRequest, restNamespace ] );

	const fetchSettings = useCallback( async () => {
		try {
			const response = await apiRequest( {
				path: `/${ restNamespace }/settings`,
			} );

			setSettings( response ?? {} );
			setInitialSettings( response ?? {} );
			setSchemaError( null );
			setError( null );
		} catch ( fetchError ) {
			setError( fetchError.message );
		} finally {
			setIsLoadingSettings( false );
		}
	}, [ apiRequest, restNamespace ] );

	/**
	 * Run an async callback while flagging `isLoadingAction` as true.
	 *
	 * Counter-based so concurrent callers (built-in `triggerAction` plus any
	 * consumer hook calling `withLoading`) don't clear the busy state for
	 * each other. Re-throws on error after decrementing the counter, so
	 * consumers can wrap their call in their own `try/catch` if needed.
	 *
	 * Exposed via `MilliBase.hooks.useSettings()` for consumer plugins to
	 * mark custom async work as "in progress" — every `<ButtonField>` and
	 * any UI driven by `isLoadingAction` will show busy for the duration.
	 *
	 * @param {() => Promise<*>} fn The async callback to run.
	 * @return {Promise<*>} Whatever `fn` resolves to.
	 */
	const withLoading = useCallback( async ( fn ) => {
		setActionLoadingCount( ( c ) => c + 1 );
		try {
			return await fn();
		} finally {
			setActionLoadingCount( ( c ) => c - 1 );
		}
	}, [] );

	/**
	 * Persist dirty settings against the namespaced settings endpoint.
	 * Shared by `saveSettings` and the chain-mode `__save` step.
	 *
	 * @return {Promise<boolean>} `true` if a POST happened, `false` when already clean.
	 */
	const persistDirtySettings = useCallback( async () => {
		if ( ! hasChangesRef.current ) {
			return false;
		}
		await apiRequest( {
			path: `/${ restNamespace }/settings`,
			method: 'POST',
			data: settingsRef.current,
		} );
		setInitialSettings( settingsRef.current );
		setHasChanges( false );
		return true;
	}, [ apiRequest, restNamespace ] );

	/**
	 * Dispatch one action step. `__save` delegates to persistDirtySettings;
	 * other names hit a custom-action endpoint or fall back to the
	 * settings actions endpoint (built-in `__reset` / `__restore`).
	 *
	 * @param {string} step The action name.
	 * @param {Object} data Payload merged into every non-`__save` POST.
	 * @return {Promise<{ success: boolean, message?: string }>}
	 */
	const dispatchStep = useCallback(
		async ( step, data ) => {
			if ( step === '__save' ) {
				await persistDirtySettings();
				return { success: true, message: '' };
			}

			const customAction = ( config.actions || [] ).find(
				( a ) => a.name === step
			);
			const path = customAction
				? `/${ restNamespace }/${ customAction.endpoint }`
				: `/${ restNamespace }/settings/actions`;

			return await apiRequest( {
				path,
				method: 'POST',
				data: { action: step, ...data },
			} );
		},
		[ apiRequest, persistDirtySettings, restNamespace, config.actions ]
	);

	/**
	 * Run one or more action steps as a sequential chain. Stops at the
	 * first non-success; earlier successful steps are not rolled back.
	 * One trailing snackbar + one settings/status refetch on success.
	 * Built-in names (`__save`, `__reset`, `__restore`) are reserved.
	 *
	 * @param {string|string[]} action One step name or a chain of names.
	 * @param {Object}          data   Payload merged into every non-`__save` POST.
	 * @return {Promise<void>}
	 */
	const triggerAction = useCallback(
		async ( action, data = {} ) => {
			const steps = Array.isArray( action ) ? action : [ action ];

			return withLoading( async () => {
				const result = await runActionChain( steps, ( step ) =>
					dispatchStep( step, data )
				);

				if ( ! result.ok ) {
					const errorText =
						result.error?.message ||
						__( 'Action failed', 'millibase' );
					showSnackbarRef.current( errorText, [], 6000, true );
					throw result.error;
				}

				await delay( 800 );

				if ( result.lastResponse?.message ) {
					showSnackbarRef.current( result.lastResponse.message );
				}
				if ( result.lastResponse?.reload ) {
					window.location.reload();
					return;
				}
				fetchSettings();
				fetchStatus();
			} );
		},
		[ withLoading, dispatchStep, fetchSettings, fetchStatus ]
	);

	const retryConnection = useCallback( async () => {
		setIsRetrying( true );
		setError( null );
		try {
			await Promise.all( [ fetchSettings(), fetchStatus() ] );
		} finally {
			setIsRetrying( false );
		}
	}, [ fetchSettings, fetchStatus ] );

	useEffect( () => {
		errorRef.current = error;
	}, [ error ] );
	useEffect( () => {
		initialSettingsRef.current = initialSettings;
	}, [ initialSettings ] );
	useEffect( () => {
		settingsRef.current = settings;
	}, [ settings ] );
	useEffect( () => {
		statusRef.current = status;
	}, [ status ] );

	// Refetch settings when polled status signals an external mutation
	// (CLI reset, other-tab save). Fingerprint covers the two flags the
	// UI gates on; flipping either means the stored option diverged
	// from this tab's view and fields need re-syncing.
	useEffect( () => {
		const settingsStatus = status?.settings;
		if ( ! settingsStatus ) {
			return;
		}

		const fingerprint = `${ settingsStatus.has_defaults }|${ settingsStatus.has_backup }`;
		const previous = lastStatusFingerprintRef.current;
		lastStatusFingerprintRef.current = fingerprint;

		if ( previous !== null && previous !== fingerprint ) {
			fetchSettings();
		}
	}, [ status, fetchSettings ] );
	useEffect( () => {
		hasChangesRef.current = hasChanges;
	}, [ hasChanges ] );
	useEffect( () => {
		hasStorageChangesRef.current = hasStorageChanges;
	}, [ hasStorageChanges ] );
	useEffect( () => {
		showSnackbarRef.current = showSnackbar;
	}, [ showSnackbar ] );

	useEffect( () => {
		fetchSettings();
		fetchStatus();

		if ( statusIntervalRef.current ) {
			clearInterval( statusIntervalRef.current );
		}

		statusIntervalRef.current = setInterval( () => {
			if ( ! errorRef.current ) {
				fetchStatus();
			}
		}, 15000 );

		return () => {
			if ( statusIntervalRef.current ) {
				clearInterval( statusIntervalRef.current );
			}
		};
	}, [ fetchSettings, fetchStatus ] );

	// Sync the active tab from the URL hash for deep links and in-app hash
	// navigation (e.g. ?page=millicache#settings). Unknown/empty hashes fall
	// back to the first tab. Uses the plain setActiveTab setter — not
	// setActiveTabWithHash — so reacting to a hash change never rewrites the
	// hash and re-triggers itself.
	useEffect( () => {
		const syncTabFromHash = () => {
			const hash = window.location.hash.replace( '#', '' );
			const tabNames = ( config.schema?.tabs || [] ).map(
				( tab ) => tab.name
			);
			const next =
				hash && tabNames.includes( hash )
					? hash
					: ( tabNames[ 0 ] ?? null );
			setActiveTab( next );
		};

		window.addEventListener( 'hashchange', syncTabFromHash );
		return () =>
			window.removeEventListener( 'hashchange', syncTabFromHash );
	}, [ config.schema ] );

	const updateSetting = useCallback( ( module, key, value ) => {
		setSettings( ( prev ) => {
			const safePrev = prev ?? {};
			const updated = {
				...safePrev,
				[ module ]: {
					...( safePrev[ module ] ?? {} ),
					[ key ]: value,
				},
			};

			setHasChanges(
				JSON.stringify( updated ) !==
					JSON.stringify( initialSettingsRef.current )
			);

			if ( module === 'storage' ) {
				setHasStorageChanges( true );
			}

			return updated;
		} );
	}, [] );

	const saveSettings = useCallback( async () => {
		if ( ! hasChangesRef.current ) {
			return;
		}

		try {
			setIsSaving( true );

			await persistDirtySettings();

			showSnackbarRef.current(
				__( 'Settings saved successfully.', 'millibase' )
			);

			if ( hasStorageChangesRef.current ) {
				const previousStatus = { ...statusRef.current };
				await delay( 500 );
				showSnackbarRef.current(
					__(
						'Storage settings updated. Testing connection…',
						'millibase'
					)
				);

				await delay( 3000 );
				const newStatus = await fetchStatus();

				if ( newStatus && previousStatus ) {
					if (
						previousStatus.storage?.connected &&
						! newStatus.storage?.connected
					) {
						await delay( 50 );
						showSnackbarRef.current(
							__( 'Storage connection lost.', 'millibase' )
						);
					} else if (
						! previousStatus.storage?.connected &&
						newStatus.storage?.connected
					) {
						showSnackbarRef.current(
							__( 'Storage connection established.', 'millibase' )
						);
					}
					if ( newStatus.storage?.error ) {
						showSnackbarRef.current(
							newStatus.storage.error,
							[],
							6000,
							true
						);
					}
				}

				setHasStorageChanges( false );
			}
		} catch ( saveError ) {
			const errorMessage =
				saveError.message ||
				__( 'Failed to save settings.', 'millibase' );
			showSnackbarRef.current( errorMessage, [], 6000, true );
		} finally {
			setTimeout( () => setIsSaving( false ), 1200 );
		}
	}, [ persistDirtySettings, fetchStatus ] );

	// Derived legacy alias — kept so custom components and buttons registered
	// by consumer plugins (passed via Header/TabRenderer) still receive the
	// any-busy flag they expect.
	const isLoading = isLoadingSettings || isLoadingAction;

	const contextValue = useMemo(
		() => ( {
			config,
			status,
			settings,
			error,
			schemaError,
			isLoadingSettings,
			isLoadingAction,
			withLoading,
			isLoading,
			isSaving,
			hasChanges,
			updateSetting,
			saveSettings,
			triggerAction,
			activeTab,
			setActiveTab: setActiveTabWithHash,
			retryConnection,
			isRetrying,
		} ),
		[
			config,
			status,
			settings,
			error,
			schemaError,
			isLoadingSettings,
			isLoadingAction,
			withLoading,
			isLoading,
			isSaving,
			hasChanges,
			activeTab,
			updateSetting,
			saveSettings,
			triggerAction,
			setActiveTabWithHash,
			retryConnection,
			isRetrying,
		]
	);

	return (
		<SettingsContext.Provider value={ contextValue }>
			{ children }
		</SettingsContext.Provider>
	);
};

/**
 * Access the MilliBase settings context.
 *
 * Stable identity guarantees (safe in useCallback/useEffect deps):
 * - setActiveTab (useCallback, [])
 * - updateSetting (useCallback, [])
 * - saveSettings (useCallback, stable deps)
 * - triggerAction (useCallback, stable deps)
 * - retryConnection (useCallback)
 *
 * The context value itself is memoized and only updates when
 * an underlying state value actually changes.
 */
export const useSettings = () => {
	return useContext( SettingsContext );
};
