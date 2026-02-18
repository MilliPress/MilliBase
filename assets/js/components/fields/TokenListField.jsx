import { FormTokenField } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const TokenListField = ( { field, value, onChange, disabled } ) => (
	<FormTokenField
		__next40pxDefaultSize
		label={
			field.tooltip ? (
				<LabelWithTooltip label={ field.label } tooltip={ field.tooltip } />
			) : (
				field.label
			)
		}
		placeholder={ field.placeholder || '' }
		value={ Array.isArray( value ) ? value : [] }
		disabled={ disabled }
		onChange={ onChange }
		suggestions={ [] }
	/>
);

export default TokenListField;
