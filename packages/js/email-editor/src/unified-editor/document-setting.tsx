/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
// @ts-expect-error Missing types for PluginDocumentSettingPanel
import { PluginDocumentSettingPanel } from '@wordpress/editor'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import { DetailsPanelContent } from '../components/sidebar/details-panel-content';
import { PostStatusPanel } from '../components/sidebar/post-status-panel';

export function DocumentSettings() {
	return (
		<PluginDocumentSettingPanel
			name="details-panel"
			title={ __( 'Details', 'mailpoet' ) }
			className="details-panel mailpoet-email-editor__settings-panel"
		>
			<DetailsPanelContent />
		</PluginDocumentSettingPanel>
	);
}

export function TemplatePanel() {
	return (
		<PluginDocumentSettingPanel
			name="template-panel"
			title={ __( 'Template', 'mailpoet' ) }
			className="mailpoet-email-editor__template-panel"
		>
			<PostStatusPanel />
		</PluginDocumentSettingPanel>
	);
}
