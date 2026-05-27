import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

// Recognizable-identifier field (license keys, API tokens). Renders as a
// plain text input; password-manager and spellcheck are opted out so the
// browser doesn't treat the value like a credential. When the server
// returns a masked value (e.g. `MILL•••••DDDD`) it's shown as the input's
// placeholder rather than its value, so pasting a new key replaces the
// hint cleanly and an untouched field round-trips the mask on save.
const KeyField = ( { field, value, onChange, disabled } ) => {
	const isMasked = typeof value === 'string' && value.includes( '•' );
	return (
		<InputControl
			__next40pxDefaultSize
			type="text"
			autoComplete="off"
			spellCheck={ false }
			className="millibase-key-field"
			label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
			help={ field.help }
			value={ isMasked ? '' : ( value ?? '' ) }
			disabled={ disabled }
			onChange={ onChange }
			onFocus={ ( e ) => e.target.select() }
			placeholder={ isMasked ? value : ( field.placeholder || '' ) }
			suffix={
				isMasked && ! disabled ? (
					<Button
						className="millibase-field-clear"
						icon={ closeSmall }
						iconSize={ 16 }
						size="small"
						label={ __( 'Clear stored value', 'millibase' ) }
						onClick={ () => onChange( '' ) }
					/>
				) : undefined
			}
		/>
	);
};

export default KeyField;
