/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordEvent } from '../../events';
import { DetailsPanelContent } from './details-panel-content';

export function DetailsPanel() {


	return (
		<PanelBody
			title={ __( 'Details', 'mailpoet' ) }
			className="mailpoet-email-editor__settings-panel"
			onToggle={ ( data ) =>
				recordEvent( 'details_panel_body_toggle', { opened: data } )
			}
		>
			<DetailsPanelContent />
		</PanelBody>
	);
}
