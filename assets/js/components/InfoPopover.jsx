/**
 * Info "ⓘ" trigger that opens a popover explaining a metric. Exposed via
 * `window.MilliBase.components.InfoPopover`. Receives `info` = { title?, description, url? }.
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Popover, Icon } from '@wordpress/components';
import { info, external } from '@wordpress/icons';

// One popover open at a time: an opening trigger tells the others to close.
const OPEN_INFO_EVENT = 'millibase:info-popover-open';

const InfoPopover = ( { info: meta } ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const containerRef = useRef( null );

	// Popover's own focus-based close doesn't cover click/Esc; wire them here.
	useEffect( () => {
		if ( ! isOpen ) {
			return undefined;
		}

		window.dispatchEvent( new CustomEvent( OPEN_INFO_EVENT ) );

		const onMouseDown = ( e ) => {
			if ( containerRef.current?.contains( e.target ) ) {
				return;
			}
			if ( e.target?.closest?.( '.millibase-info-popover' ) ) {
				return;
			}
			setIsOpen( false );
		};
		const onKeyDown = ( e ) => {
			if ( e.key === 'Escape' ) {
				setIsOpen( false );
			}
		};
		const onOtherOpen = () => setIsOpen( false );

		document.addEventListener( 'mousedown', onMouseDown );
		document.addEventListener( 'keydown', onKeyDown );
		window.addEventListener( OPEN_INFO_EVENT, onOtherOpen );

		return () => {
			document.removeEventListener( 'mousedown', onMouseDown );
			document.removeEventListener( 'keydown', onKeyDown );
			window.removeEventListener( OPEN_INFO_EVENT, onOtherOpen );
		};
	}, [ isOpen ] );

	if ( ! meta?.description ) {
		return null;
	}

	const label = meta.title ?? __( 'About this metric', 'millibase' );

	return (
		<div
			ref={ containerRef }
			className="millibase-info-trigger-wrapper"
			tabIndex={ -1 }
		>
			<Button
				className="millibase-info-trigger"
				icon={ info }
				iconSize={ 16 }
				size="small"
				label={ label }
				showTooltip={ false }
				onClick={ () => setIsOpen( ( prev ) => ! prev ) }
				aria-expanded={ isOpen }
			/>
			{ isOpen && (
				<Popover
					placement="top"
					offset={ 8 }
					noArrow={ false }
					onClose={ () => setIsOpen( false ) }
					className="millibase-info-popover"
					focusOnMount="firstElement"
				>
					<div className="millibase-info-popover__content">
						{ meta.title && (
							<h4 className="millibase-info-popover__title">
								{ meta.title }
							</h4>
						) }
						<p>{ meta.description }</p>
						{ meta.url && (
							<a
								href={ meta.url }
								target="_blank"
								rel="noopener noreferrer"
								className="millibase-info-popover__link"
							>
								{ __( 'Learn more', 'millibase' ) }
								<Icon icon={ external } size={ 12 } />
							</a>
						) }
					</div>
				</Popover>
			) }
		</div>
	);
};

export default InfoPopover;