/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { recordEvent } from '../../events';
import { RichTextWithButton } from '../personalization-tags/rich-text-with-button';

const SidebarExtensionComponent = applyFilters(
	'mailpoet_email_editor_setting_sidebar_extension_component',
	RichTextWithButton
) as () => JSX.Element;

export function DetailsPanel() {
	return (
		<PanelBody
			title={ __( 'Details', 'mailpoet' ) }
			className="mailpoet-email-editor__settings-panel"
			onToggle={ ( data ) =>
				recordEvent( 'details_panel_body_toggle', { opened: data } )
			}
		>
			<>{ <SidebarExtensionComponent /> }</>
		</PanelBody>
	);
}
