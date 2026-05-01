import renderHelpText from '../../utils/renderHelpText.jsx';

describe( 'renderHelpText', () => {
	it( 'returns null for empty / non-string input', () => {
		expect( renderHelpText( '' ) ).toBeNull();
		expect( renderHelpText( null ) ).toBeNull();
		expect( renderHelpText( undefined ) ).toBeNull();
		expect( renderHelpText( 42 ) ).toBeNull();
	} );

	it( 'returns the input as a single-string array when there are no links', () => {
		expect( renderHelpText( 'Plain help text' ) ).toEqual( [
			'Plain help text',
		] );
	} );

	it( 'parses a single inline link with surrounding text', () => {
		const result = renderHelpText(
			'See [docs](https://example.test) for details.'
		);

		expect( result ).toHaveLength( 3 );
		expect( result[ 0 ] ).toBe( 'See ' );
		expect( result[ 1 ].type ).toBe( 'a' );
		expect( result[ 1 ].props.href ).toBe( 'https://example.test' );
		expect( result[ 1 ].props.target ).toBe( '_blank' );
		expect( result[ 1 ].props.rel ).toBe( 'noopener noreferrer' );
		expect( result[ 1 ].props.children ).toBe( 'docs' );
		expect( result[ 2 ] ).toBe( ' for details.' );
	} );

	it( 'omits target on root-relative URLs so same-site links stay in the current tab', () => {
		const result = renderHelpText(
			'Edit [permalinks](/wp-admin/options-permalink.php).'
		);

		expect( result[ 1 ].type ).toBe( 'a' );
		expect( result[ 1 ].props.href ).toBe(
			'/wp-admin/options-permalink.php'
		);
		expect( result[ 1 ].props.target ).toBeUndefined();
		expect( result[ 1 ].props.rel ).toBeUndefined();
	} );

	it( 'omits target on mailto / tel — the browser protocol handler takes over', () => {
		const mail = renderHelpText( '[Email](mailto:support@example.test)' );
		expect( mail[ 0 ].props.href ).toBe( 'mailto:support@example.test' );
		expect( mail[ 0 ].props.target ).toBeUndefined();

		const phone = renderHelpText( '[Call](tel:+15551234567)' );
		expect( phone[ 0 ].props.href ).toBe( 'tel:+15551234567' );
		expect( phone[ 0 ].props.target ).toBeUndefined();
	} );

	it( 'parses multiple links in order with text between them', () => {
		const result = renderHelpText(
			'A [one](https://a.test) and [two](https://b.test) here.'
		);

		expect( result ).toHaveLength( 5 );
		expect( result[ 0 ] ).toBe( 'A ' );
		expect( result[ 1 ].props.href ).toBe( 'https://a.test' );
		expect( result[ 2 ] ).toBe( ' and ' );
		expect( result[ 3 ].props.href ).toBe( 'https://b.test' );
		expect( result[ 4 ] ).toBe( ' here.' );
	} );

	it( 'gives every link a stable key so React reconciliation is happy', () => {
		const result = renderHelpText(
			'[a](https://a.test)[b](https://b.test)'
		);

		expect( result ).toHaveLength( 2 );
		expect( result[ 0 ].key ).toBe( 'link-0' );
		expect( result[ 1 ].key ).toBe( 'link-1' );
	} );

	it( 'renders all safe schemes as clickable links', () => {
		const safeUrls = [
			'https://example.test/path',
			'http://legacy.test',
			'mailto:support@example.test',
			'tel:+15551234567',
			'/customer-dashboard',
		];

		for ( const url of safeUrls ) {
			const result = renderHelpText( `[click](${ url })` );

			expect( result ).toHaveLength( 1 );
			expect( result[ 0 ].type ).toBe( 'a' );
			expect( result[ 0 ].props.href ).toBe( url );
		}
	} );

	it( 'renders only the label (no link, no markdown syntax) for unsafe URL schemes', () => {
		const unsafeUrls = [
			'javascript:alert(1)',
			'data:text/html,<script>alert(1)</script>',
			'vbscript:msgbox',
			'file:///etc/passwd',
			'ftp://files.test',
		];

		for ( const url of unsafeUrls ) {
			const result = renderHelpText( `[click here](${ url })` );

			expect( result ).toEqual( [ 'click here' ] );
		}
	} );

	it( 'leaves text with unbalanced markdown alone (no partial render)', () => {
		expect( renderHelpText( 'See [docs(https://x.test).' ) ).toEqual( [
			'See [docs(https://x.test).',
		] );
		expect( renderHelpText( 'See ]docs](https://x.test).' ) ).toEqual( [
			'See ]docs](https://x.test).',
		] );
		expect( renderHelpText( 'No (parens here) at all.' ) ).toEqual( [
			'No (parens here) at all.',
		] );
	} );

	it( 'preserves whitespace and newlines around links', () => {
		const result = renderHelpText(
			'   [docs](https://x.test)\n\nFooter line'
		);

		expect( result[ 0 ] ).toBe( '   ' );
		expect( result[ 1 ].type ).toBe( 'a' );
		expect( result[ 2 ] ).toBe( '\n\nFooter line' );
	} );
} );
