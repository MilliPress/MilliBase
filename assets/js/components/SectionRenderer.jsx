/**
 * Renders a PanelBody with grouped fields from a section definition.
 */

import { PanelBody, Flex } from '@wordpress/components';
import FieldRenderer from './FieldRenderer.jsx';
import { useSettings } from './SettingsProvider.jsx';

const SectionRenderer = ( { section } ) => {
	const { settings, updateSetting } = useSettings();

	return (
		<PanelBody
			title={ section.title }
			initialOpen={ section.initial_open !== false }
		>
			<Flex direction="column" gap="4">
				{ ( section.fields || [] ).map( ( field ) => {
					const parts = field.key.split( '.' );
					const module = parts[ 0 ];
					const key = parts[ 1 ];
					const value = settings?.[ module ]?.[ key ] ?? field.default;
					const disabled = settings?.[ module ]
						? ! ( key in settings[ module ] )
						: false;

					return (
						<FieldRenderer
							key={ field.key }
							field={ field }
							value={ value }
							onChange={ ( newValue ) =>
								updateSetting( module, key, newValue )
							}
							disabled={ disabled }
						/>
					);
				} ) }
			</Flex>
		</PanelBody>
	);
};

export default SectionRenderer;
