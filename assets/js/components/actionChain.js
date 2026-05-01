/**
 * Run an ordered list of action steps, stopping at the first non-success
 * (or thrown dispatch error). On failure, `lastResponse` is the failing
 * step's response (or the last successful one if dispatch threw).
 *
 * @param {string[]}                                                          steps    Ordered step names.
 * @param {(step: string) => Promise<{ success: boolean, message?: string }>} dispatch Called once per step.
 * @return {Promise<{ ok: boolean, lastResponse: object|null, error?: Error }>}
 *                   Chain outcome.
 */
export async function runActionChain( steps, dispatch ) {
	let lastResponse = null;

	for ( const step of steps ) {
		let response;
		try {
			response = await dispatch( step );
		} catch ( dispatchError ) {
			return {
				ok: false,
				lastResponse,
				error:
					dispatchError instanceof Error
						? dispatchError
						: new Error( String( dispatchError ) ),
			};
		}

		if ( ! response || ! response.success ) {
			return {
				ok: false,
				lastResponse: response ?? lastResponse,
				error: new Error( response?.message || 'Action failed' ),
			};
		}

		lastResponse = response;
	}

	return { ok: true, lastResponse };
}
