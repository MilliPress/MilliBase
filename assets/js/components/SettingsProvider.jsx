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

	/**
	 * Dispatch a single action step against the REST API.
	 *
	 * Resolves three cases: the built-in `__save` step (mirrors the
	 * dirty-settings save flow against `/wp/v2/settings`), the built-in
	 * `__reset` / `__restore` steps (handled server-side by
	 * `Controller::perform_settings_action` via the namespaced settings
	 * endpoint), and consumer-registered custom actions (looked up by name
	 * in `config.actions` and dispatched to their declared endpoint).
	 *
	 * `__save` is a silent no-op when there are no pending changes —
	 * chains like `[ '__save', 'license_activate' ]` therefore stay safe
	 * to re-click after a successful run, since the second invocation
	 * falls through the save step and re-runs the action with the same
	 * input. Returns an empty-message envelope in that case so chain-mode
	 * callers can suppress per-step snackbars without special-casing.
	 *
	 * @param {string} step The action name (built-in or custom).
	 * @param {Object} data Optional payload merged into every non-`__save` POST.
	 * @return {Promise<{ success: boolean, message?: string }>}
	 */
	const dispatchStep = useCallback(
		async ( step, data ) => {
			if ( step === '__save' ) {
				if ( ! hasChangesRef.current ) {
					return { success: true, message: '' };
				}
				await apiRequest( {
					path: '/wp/v2/settings',
					method: 'POST',
					data: { [ optionName ]: settingsRef.current },
				} );
				setInitialSettings( settingsRef.current );
				setHasChanges( false );
				return { success: true, message: '' };
			}

			const customAction = ( config.actions || [] ).find(
				( a ) => a.name === step
			);
			const path = customAction
				? `/${ restNamespace }/${ customAction.endpoint }`
				: `/${ restNamespace }/settings`;

			return await apiRequest( {
				path,
				method: 'POST',
				data: { action: step, ...data },
			} );
		},
		[ apiRequest, optionName, restNamespace, config.actions ]
	);

	/**
	 * Run one or more action steps as a sequential chain.
	 *
	 * Accepts a single action name or an array of names. The chain runs
	 * sequentially, stops at the first non-success response, and surfaces
	 * one trailing snackbar plus a single settings + status refresh — so
	 * a two-step chain like `[ '__save', 'license_activate' ]` reads as
	 * one operation to the user (one busy span, one toast, one refetch).
	 *
	 * Failure semantics: the failing step's `message` is shown via the
	 * error snackbar. Earlier successful steps are not rolled back —
	 * `__save` writing to the option is the canonical example: if a
	 * subsequent action fails the saved settings stand, which matches the
	 * mental model that Save was the user's first commitment anyway.
	 *
	 * Built-in step names (`__save`, `__reset`, `__restore`) are reserved
	 * by the framework. Consumer-registered actions use un-prefixed names
	 * matched against `config.actions`. Single-string callers
	 * (`triggerAction( 'license_activate' )`) keep working unchanged — a
	 * one-element chain is exactly equivalent to the legacy single call.
	 *
	 * @param {string|string[]} action One step name or a sequence of names.
	 * @param {Object}          data   Optional payload merged into every POST.
	 * @return {Promise<void>}
	 */
	const triggerAction = useCallback(
		async ( action, data = {} ) => {
			const steps = Array.isArray( action ) ? action : [ action ];

			return withLoading( async () => {
				let lastResponse = null;
				try {
					for ( const step of steps ) {
						const response = await dispatchStep( step, data );
						if ( ! response.success ) {
							throw new Error(
								response.message ||
									__( 'Action failed', 'millibase' )
							);
						}
						lastResponse = response;
					}
				} catch ( actionError ) {
					const errorText =
						actionError.message ||
						__( 'Action failed', 'millibase' );
					showSnackbarRef.current( errorText, [], 6000, true );
					throw actionError;
				}

				await delay( 800 );

				if ( lastResponse?.message ) {
					showSnackbarRef.current( lastResponse.message );
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
