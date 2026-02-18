import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const PasswordField = ( { field, value, onChange, disabled } ) => (
	<InputControl
		__next40pxDefaultSize
		type="password"
		label={
			field.tooltip ? (
				<LabelWithTooltip label={ field.label } tooltip={ field.tooltip } />
			) : (
				field.label
			)
		}
		value={ value ?? '' }
		disabled={ disabled }
		onChange={ onChange }
	/>
);

export default PasswordField;
