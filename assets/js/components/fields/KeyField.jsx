import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

// Recognizable-identifier field (license keys, API tokens).
const KeyField = ( { field, value, onChange, disabled } ) => {
	const isMasked = typeof value === 'string' && value.includes( '•' );
	const hintOnly = isMasked && ! disabled;
	return (
		<InputControl
			__next40pxDefaultSize
			type="text"
			autoComplete="off"
			spellCheck={ false }
			className="millibase-key-field"
			label={
				<LabelWithTooltip
					label={ field.label }
					tooltip={ field.tooltip }
				/>
			}
			help={ field.help }
			value={ hintOnly ? '' : value ?? '' }
			disabled={ disabled }
			onChange={ onChange }
			onFocus={ ( e ) => e.target.select() }
			placeholder={ hintOnly ? value : field.placeholder || '' }
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
