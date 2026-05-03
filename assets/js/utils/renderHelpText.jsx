/**
 * Render a help string with inline `[label](url)` markdown-style links into
 * a React node array suitable for wp-components' `help` prop. Plain strings
 * pass through; malformed markdown renders verbatim.
 *
 * Allowed schemes: `https://`, `http://`, `mailto:`, `tel:`, and root-relative
 * paths. Anything else renders the label as plain text.
 *
 * `http(s)://` opens in a new tab with `noopener noreferrer`; relative /
 * `mailto:` / `tel:` stay in the current tab.
 *
 * @param {string} text Help string, optionally with `[label](url)` markers.
 * @return {Array<string|object>|null} Mixed string/element array, or `null`
 *                                     when input is empty / not a string.
 */
// Tolerates one level of nested parens (Wikipedia-style URLs); deeper
// nesting would need a real parser.
const LINK_PATTERN = /\[([^\]]+)\]\(([^()]*(?:\([^()]*\)[^()]*)*)\)/g;
const SAFE_SCHEME = /^(?:https?:\/\/|\/|mailto:|tel:)/i;
const EXTERNAL_SCHEME = /^https?:\/\//i;

const renderHelpText = ( text ) => {
	if ( typeof text !== 'string' || text === '' ) {
		return null;
	}

	const parts = [];
	let lastIndex = 0;
	let linkIndex = 0;

	for ( const match of text.matchAll( LINK_PATTERN ) ) {
		if ( match.index > lastIndex ) {
			parts.push( text.slice( lastIndex, match.index ) );
		}

		const [ , label, url ] = match;

		if ( ! SAFE_SCHEME.test( url ) ) {
			// Strip markdown, keep the words.
			parts.push( label );
		} else {
			const externalProps = EXTERNAL_SCHEME.test( url )
				? { target: '_blank', rel: 'noopener noreferrer' }
				: {};

			parts.push(
				<a
					key={ `link-${ linkIndex++ }` }
					href={ url }
					{ ...externalProps }
				>
					{ label }
				</a>
			);
		}

		lastIndex = match.index + match[ 0 ].length;
	}

	if ( lastIndex < text.length ) {
		parts.push( text.slice( lastIndex ) );
	}

	return parts.length > 0 ? parts : null;
};

export default renderHelpText;
