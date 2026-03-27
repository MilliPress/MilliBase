import { FormTokenField } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const TokenListField = ( { field, value, onChange, disabled } ) => (
	<FormTokenField
		__next40pxDefaultSize
		label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
		placeholder={ field.placeholder || '' }
		value={ Array.isArray( value ) ? value : [] }
		disabled={ disabled }
		onChange={ onChange }
		suggestions={ [] }
	/>
);

export default TokenListField;
