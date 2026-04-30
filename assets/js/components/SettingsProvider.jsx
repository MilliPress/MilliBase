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

const SettingsContext = createContext();

export const SettingsProvider = ( { config, children } ) => {
	const { optionName, restNamespace } = config;

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
					message = __( 'Security check failed. Please refresh.', 'millibase' );
					break;
				default:
					message = apiError.message || __( 'API request failed.', 'millibase' );
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
			const response = await apiRequest( { path: '/wp/v2/settings' } );
			const optionValue = response?.[ optionName ];

			// `null` here means WP REST rejected the stored value against
			// its registered schema (see WP_REST_Settings_Controller::prepare_value).
			// Tracked in its own state so concurrent fetchStatus calls — which
			// own the connection-level `error` — can't blank this message out.
			if ( null === optionValue ) {
				setSchemaError(
					__(
						'A stored value does not match the registered schema — typically a field with the wrong type (for example a string where a number is expected).',
						'millibase'
					)
				);
				return;
			}

			setSettings( optionValue ?? {} );
			setInitialSettings( optionValue ?? {} );
			setSchemaError( null );
			setError( null );
		} catch ( fetchError ) {
			setError( fetchError.message );
		} finally {
			setIsLoadingSettings( false );
		}
	}, [ apiRequest, optionName ] );

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

	const triggerAction = useCallback( async ( action, data = {} ) => {
		return withLoading( async () => {
			try {
				// Determine endpoint: check if it matches a custom action.
				let path = `/${ restNamespace }/settings`;
				const customAction = ( config.actions || [] ).find(
					( a ) => a.name === action
				);
				if ( customAction ) {
					path = `/${ restNamespace }/${ customAction.endpoint }`;
				}

				const response = await apiRequest( {
					path,
					method: 'POST',
					data: { action, ...data },
				} );

				await delay( 800 );

				if ( response.success ) {
					showSnackbarRef.current( response.message );
					fetchSettings();
					fetchStatus();
				} else {
					throw new Error(
						response.message || __( 'Action failed', 'millibase' )
					);
				}
			} catch ( actionError ) {
				const errorText =
					actionError.message || __( 'Action failed', 'millibase' );
				showSnackbarRef.current( errorText, [], 6000, true );
				throw actionError;
			}
		} );
	}, [ withLoading, restNamespace, config.actions, apiRequest, fetchSettings, fetchStatus ] );

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

			await apiRequest( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: { [ optionName ]: settingsRef.current },
			} );

			setInitialSettings( settingsRef.current );
			showSnackbarRef.current(
				__( 'Settings saved successfully.', 'millibase' )
			);
			setHasChanges( false );

			if ( hasStorageChangesRef.current ) {
				const previousStatus = { ...statusRef.current };
				await delay( 500 );
				showSnackbarRef.current(
					__( 'Storage settings updated. Testing connection…', 'millibase' )
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
	}, [ apiRequest, optionName, fetchStatus ] );

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
