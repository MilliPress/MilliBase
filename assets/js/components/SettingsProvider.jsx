import {
	createContext,
	useContext,
	useState,
	useEffect,
	useCallback,
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
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ error, setError ] = useState( null );
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
	const { showSnackbar } = useSnackbar();

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

	const triggerAction = async ( action, data = {} ) => {
		setIsLoading( true );
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
				showSnackbar( response.message );
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
			showSnackbar( errorText, [], 6000, true );
			throw actionError;
		} finally {
			setIsLoading( false );
		}
	};

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
			setIsLoading( true );
			const response = await apiRequest( { path: '/wp/v2/settings' } );
			setSettings( response?.[ optionName ] );
			setInitialSettings( response?.[ optionName ] );
			setError( null );
		} catch ( fetchError ) {
			setError( fetchError.message );
		} finally {
			setIsLoading( false );
		}
	}, [ apiRequest, optionName ] );

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
		fetchSettings();
		fetchStatus();

		if ( statusIntervalRef.current ) {
			clearInterval( statusIntervalRef.current );
		}

		statusIntervalRef.current = setInterval( () => {
			if ( ! error ) {
				fetchStatus();
			}
		}, 15000 );

		return () => {
			if ( statusIntervalRef.current ) {
				clearInterval( statusIntervalRef.current );
			}
		};
	}, [ fetchSettings, fetchStatus, error ] );

	const updateSetting = ( module, key, value ) => {
		setSettings( ( prev ) => {
			const updated = {
				...prev,
				[ module ]: {
					...prev[ module ],
					[ key ]: value,
				},
			};

			setHasChanges(
				JSON.stringify( updated ) !== JSON.stringify( initialSettings )
			);

			if ( module === 'storage' ) {
				setHasStorageChanges( true );
			}

			return updated;
		} );
	};

	const saveSettings = async () => {
		if ( ! hasChanges ) {
			return;
		}

		try {
			setIsSaving( true );

			await apiRequest( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: { [ optionName ]: settings },
			} );

			setInitialSettings( settings );
			showSnackbar( __( 'Settings saved successfully.', 'millibase' ) );
			setHasChanges( false );

			if ( hasStorageChanges ) {
				const previousStatus = { ...status };
				await delay( 500 );
				showSnackbar(
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
						showSnackbar(
							__( 'Storage connection lost.', 'millibase' )
						);
					} else if (
						! previousStatus.storage?.connected &&
						newStatus.storage?.connected
					) {
						showSnackbar(
							__( 'Storage connection established.', 'millibase' )
						);
					}
					if ( newStatus.storage?.error ) {
						showSnackbar( newStatus.storage.error, [], 6000, true );
					}
				}

				setHasStorageChanges( false );
			}
		} catch ( saveError ) {
			const errorMessage =
				saveError.message || __( 'Failed to save settings.', 'millibase' );
			showSnackbar( errorMessage, [], 6000, true );
		} finally {
			setTimeout( () => setIsSaving( false ), 1200 );
		}
	};

	return (
		<SettingsContext.Provider
			value={ {
				config,
				status,
				settings,
				error,
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
			} }
		>
			{ children }
		</SettingsContext.Provider>
	);
};

export const useSettings = () => {
	return useContext( SettingsContext );
};
