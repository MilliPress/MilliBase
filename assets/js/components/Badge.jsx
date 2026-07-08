/**
 * Renders a small pill label — used for static section badges (e.g. "Beta")
 * and the runtime status badge.
 */

const TONES = {
	ok: { backgroundColor: '#e3f5e1', color: '#00a32a' },
	error: { backgroundColor: '#fcecec', color: '#d63638' },
	warning: { backgroundColor: '#fcf9e8', color: '#996800' },
	info: { backgroundColor: '#e5f1f8', color: '#0a4b78' },
};

/**
 * @param {Object}                        props          Component properties.
 * @param {'ok'|'error'|'warning'|'info'} [props.tone]   Color tone. Defaults to 'info'.
 * @param {string|React.ReactNode}        props.children The badge label.
 * @return {React.ReactElement}
 */
const Badge = ( { tone = 'info', children } ) => (
	<span
		style={ {
			fontSize: '10px',
			lineHeight: '1',
			padding: '4px 8px',
			borderRadius: '9999px',
			fontWeight: 500,
			...( TONES[ tone ] || TONES.info ),
		} }
	>
		{ children }
	</span>
);

export default Badge;
