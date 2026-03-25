import { Flex, Tooltip } from '@wordpress/components';
import { Icon, help } from '@wordpress/icons';

/**
 * Renders a label with a tooltip help icon.
 *
 * @param {Object}              props              Component properties.
 * @param {string|React.ReactNode} props.label     The label text.
 * @param {string}              props.tooltip       Tooltip text.
 * @param {number}              [props.iconSize=16] Help icon size.
 * @param {string}              [props.justify]     Flex justification.
 * @param {Object}              [props.style]       Additional styles.
 * @param {Object}              [props.tooltipProps] Tooltip props.
 * @param {Object}              [props.iconProps]   Icon props.
 * @return {React.ReactElement}
 */
export const LabelWithTooltip = ( {
	label,
	tooltip,
	iconSize = 16,
	justify = 'flex-start',
	style = {},
	tooltipProps = {},
	iconProps = {},
} ) => {
	if ( ! tooltip ) {
		return label;
	}

	return (
	<Flex align="center" gap={ 1 } style={ style } justify={ justify }>
		<span>{ label }</span>
		<Tooltip
			text={ tooltip }
			delay="250"
			style={ { maxWidth: '300px' } }
			{ ...tooltipProps }
		>
			<span
				className="millibase-tooltip-icon"
				style={ { display: 'flex', alignItems: 'center' } }
			>
				<Icon icon={ help } size={ iconSize } { ...iconProps } />
			</span>
		</Tooltip>
	</Flex>
	);
};
