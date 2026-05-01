import { FormTokenField } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

// `field.help` is forwarded as the native `help` prop, which lands in
// @wordpress/components 33.0.0. Older runtimes (the WP-bundled 32.x) silently
// ignore the unknown prop, so `help` is effectively a no-op on `token-list`
// until the host catches up. We deliberately do not render a manual fallback
// here — its visual treatment doesn't match the native FormTokenField help
// slot and ends up looking out of place.
const TokenListField = ( { field, value, onChange, disabled } ) => (
	<FormTokenField
		__next40pxDefaultSize
		__nextHasNoMarginBottom
		label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
		help={ field.help }
		placeholder={ field.placeholder || '' }
		value={ Array.isArray( value ) ? value : [] }
		disabled={ disabled }
		onChange={ onChange }
		suggestions={ [] }
	/>
);

export default TokenListField;
