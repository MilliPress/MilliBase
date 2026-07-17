import { useState } from '@wordpress/element';
import { Button, Modal, Flex } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as wpIcons from '@wordpress/icons';
import { useSettings } from '../SettingsProvider.jsx';

const ButtonField = ( { field, disabled } ) => {
	const { triggerAction, isLoadingAction, isSaving } = useSettings();
	const [ confirmOpen, setConfirmOpen ] = useState( false );

	const fire = () => {
		if ( field.action ) {
			triggerAction( field.action );
		}
	};

	const onClick = () => {
		if ( field.confirm ) {
			setConfirmOpen( true );
			return;
		}
		fire();
	};

	const onConfirm = () => {
		setConfirmOpen( false );
		fire();
	};

	const Icon = field.icon ? wpIcons[ field.icon ] : undefined;
	const isCompact = field.size === 'compact' || field.size === 'small';
	const sizeProps = isCompact
		? { size: field.size }
		: { __next40pxDefaultSize: true, size: 'default' };

	const button = (
		<Button
			{ ...sizeProps }
			variant={ field.variant || 'secondary' }
			isDestructive={ !! field.isDestructive }
			isBusy={ isLoadingAction }
			disabled={ disabled || isLoadingAction || isSaving }
			icon={ Icon }
			label={ field.tooltip }
			showTooltip={ !! field.tooltip }
			onClick={ onClick }
			className={
				field.width === 'auto'
					? 'millibase-button-field__button millibase-button-field__button--auto'
					: 'millibase-button-field__button'
			}
		>
			{ field.label }
		</Button>
	);

	return (
		<>
			{ field.inline ? (
				<div className="millibase-button-field--inline">
					<div
						className="millibase-button-field__label-spacer"
						aria-hidden="true"
					>
						&nbsp;
					</div>
					{ button }
				</div>
			) : (
				button
			) }

			{ confirmOpen && (
				<Modal
					title={ field.label }
					onRequestClose={ () => setConfirmOpen( false ) }
				>
					<p>{ field.confirm }</p>
					<Flex justify="flex-end" gap="2">
						<Button
							__next40pxDefaultSize
							variant="tertiary"
							onClick={ () => setConfirmOpen( false ) }
						>
							{ __( 'Cancel', 'millibase' ) }
						</Button>
						<Button
							__next40pxDefaultSize
							variant="primary"
							isDestructive={ !! field.isDestructive }
							onClick={ onConfirm }
						>
							{ field.label }
						</Button>
					</Flex>
				</Modal>
			) }
		</>
	);
};

export default ButtonField;