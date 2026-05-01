import { runActionChain } from '../actionChain.js';

describe( 'runActionChain', () => {
	it( 'returns ok with null lastResponse for an empty step list', async () => {
		const dispatch = jest.fn();

		const result = await runActionChain( [], dispatch );

		expect( result ).toEqual( { ok: true, lastResponse: null } );
		expect( dispatch ).not.toHaveBeenCalled();
	} );

	it( 'runs a single step and returns its response as lastResponse', async () => {
		const dispatch = jest.fn().mockResolvedValue( {
			success: true,
			message: 'done',
		} );

		const result = await runActionChain( [ 'step_a' ], dispatch );

		expect( dispatch ).toHaveBeenCalledTimes( 1 );
		expect( dispatch ).toHaveBeenCalledWith( 'step_a' );
		expect( result.ok ).toBe( true );
		expect( result.lastResponse ).toEqual( {
			success: true,
			message: 'done',
		} );
	} );

	it( 'runs steps sequentially and exposes the final response', async () => {
		const responses = [
			{ success: true, message: 'a' },
			{ success: true, message: 'b' },
			{ success: true, message: 'c' },
		];
		const order = [];
		const dispatch = jest.fn().mockImplementation( async ( step ) => {
			order.push( step );
			return responses.shift();
		} );

		const result = await runActionChain(
			[ 'step_a', 'step_b', 'step_c' ],
			dispatch
		);

		expect( order ).toEqual( [ 'step_a', 'step_b', 'step_c' ] );
		expect( result.ok ).toBe( true );
		expect( result.lastResponse ).toEqual( {
			success: true,
			message: 'c',
		} );
	} );

	it( 'stops at the first non-success and surfaces the failing message', async () => {
		const dispatch = jest
			.fn()
			.mockResolvedValueOnce( { success: true, message: 'saved' } )
			.mockResolvedValueOnce( {
				success: false,
				message: 'license_revoked',
			} )
			.mockResolvedValueOnce( { success: true, message: 'never' } );

		const result = await runActionChain(
			[ '__save', 'license_activate', 'should_not_run' ],
			dispatch
		);

		expect( dispatch ).toHaveBeenCalledTimes( 2 );
		expect( result.ok ).toBe( false );
		expect( result.error ).toBeInstanceOf( Error );
		expect( result.error.message ).toBe( 'license_revoked' );
		expect( result.lastResponse ).toEqual( {
			success: false,
			message: 'license_revoked',
		} );
	} );

	it( 'falls back to a generic error message when the failing step omits one', async () => {
		const dispatch = jest.fn().mockResolvedValueOnce( { success: false } );

		const result = await runActionChain( [ 'silent_fail' ], dispatch );

		expect( result.ok ).toBe( false );
		expect( result.error.message ).toBe( 'Action failed' );
	} );

	it( 'treats a thrown dispatch error as chain failure with prior lastResponse preserved', async () => {
		const dispatch = jest
			.fn()
			.mockResolvedValueOnce( { success: true, message: 'saved' } )
			.mockRejectedValueOnce( new Error( 'network down' ) );

		const result = await runActionChain(
			[ '__save', 'license_activate' ],
			dispatch
		);

		expect( dispatch ).toHaveBeenCalledTimes( 2 );
		expect( result.ok ).toBe( false );
		expect( result.error.message ).toBe( 'network down' );
		// lastResponse from before the throw is preserved so callers
		// can distinguish "first step failed" from "step N failed".
		expect( result.lastResponse ).toEqual( {
			success: true,
			message: 'saved',
		} );
	} );

	it( 'wraps a non-Error throw into an Error', async () => {
		const dispatch = jest.fn().mockImplementationOnce( async () => {
			throw 'string thrown';
		} );

		const result = await runActionChain( [ 'step_a' ], dispatch );

		expect( result.ok ).toBe( false );
		expect( result.error ).toBeInstanceOf( Error );
		expect( result.error.message ).toBe( 'string thrown' );
	} );
} );
