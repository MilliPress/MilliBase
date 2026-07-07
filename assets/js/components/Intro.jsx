/**
 * Renders a tab or section intro: a registered custom component name,
 * a limited-HTML string, or plain text.
 *
 * Custom components render as-is with the full settings context. Other
 * content is passed to `render` so each caller controls the wrapper
 * markup. HTML strings arrive pre-sanitized by `Schema::sanitize_intro()`.
 */

import { createElement, RawHTML } from '@wordpress/element';
import { useSettings } from './SettingsProvider.jsx';
import resolveCustomComponent from '../utils/resolveCustomComponent.js';

// Component names never contain angle brackets, so a tag marks HTML content.
const CONTAINS_HTML = /<[a-z][^>]*>/i;

/**
 * @param {Object}   props        Component properties.
 * @param {string}   props.text   Intro text, HTML, or component name.
 * @param {Function} props.render Wraps non-component content in caller markup.
 * @return {React.ReactElement}
 */
const Intro = ( { text, render } ) => {
	const context = useSettings();
	const CustomIntro = resolveCustomComponent( text );

	if ( CustomIntro ) {
		return createElement( CustomIntro, context );
	}

	return render(
		CONTAINS_HTML.test( text ) ? <RawHTML>{ text }</RawHTML> : text
	);
};

export default Intro;
