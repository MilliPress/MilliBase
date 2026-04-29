/**
 * Banner: full-width centered display for empty states, errors, and recovery flows.
 *
 * Slots: icon (optional), title, message, actions (button row), footer (small text below separator).
 */

import { Icon } from '@wordpress/components';

const Banner = ( { icon, iconColor = '#646970', title, message, actions, footer } ) => (
	<div
		className="millibase-banner-container"
		style={ {
			padding: '60px 20px',
			textAlign: 'center',
			maxWidth: '600px',
			margin: '0 auto',
		} }
	>
		{ icon && (
			<div style={ { marginBottom: '24px' } }>
				<Icon
					icon={ icon }
					size={ 48 }
					style={ { color: iconColor, opacity: 0.85 } }
				/>
			</div>
		) }
		{ title && (
			<h2
				style={ {
					margin: '0 0 16px 0',
					fontSize: '24px',
					fontWeight: '600',
					color: '#1e1e1e',
				} }
			>
				{ title }
			</h2>
		) }
		{ message && (
			<p
				style={ {
					fontSize: '16px',
					lineHeight: '1.5',
					color: '#646970',
					maxWidth: '500px',
					margin: '0 auto 32px auto',
				} }
			>
				{ message }
			</p>
		) }
		{ actions && (
			<div
				style={ {
					marginBottom: footer ? '32px' : '0',
					display: 'flex',
					justifyContent: 'center',
					gap: '12px',
					flexWrap: 'wrap',
				} }
			>
				{ actions }
			</div>
		) }
		{ footer && (
			<div
				style={ {
					borderTop: '1px solid #e0e0e0',
					paddingTop: '24px',
					color: '#646970',
					fontSize: '14px',
					lineHeight: '1.5',
				} }
			>
				{ footer }
			</div>
		) }
	</div>
);

export default Banner;
