import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const TextField = ( { field, value, onChange, disabled } ) => (
	<InputControl
		__next40pxDefaultSize
		label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
		value={ value ?? '' }
		disabled={ disabled }
		onChange={ onChange }
		placeholder={ field.placeholder || '' }
	/>
);

export default TextField;
