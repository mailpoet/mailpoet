/**
 * External dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { DetailsPanelContent } from "../components/sidebar/details-panel-content";

export function DocumentSettings() {
	return (<PluginDocumentSettingPanel
		name="details-panel"
		title={__('Details', 'mailpoet')}
		className="details-panel"
	>
		<DetailsPanelContent />
	</PluginDocumentSettingPanel>);
};
