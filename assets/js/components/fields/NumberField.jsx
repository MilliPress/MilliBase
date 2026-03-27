import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const NumberField = ( { field, value, onChange, disabled } ) => (
	<NumberControl
		__next40pxDefaultSize
		label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
		value={ value ?? 0 }
		disabled={ disabled }
		min={ field.min }
		max={ field.max }
		onChange={ onChange }
	/>
);

export default NumberField;
