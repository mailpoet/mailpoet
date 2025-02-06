/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { styles } from '@wordpress/icons';
// @ts-expect-error Missing types for PluginSidebar, PluginSidebarMoreMenuItem
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import { StylesSidebarContent } from '../components/styles-sidebar';

export function StylesSidebar() {
	return (
		<>
			<PluginSidebarMoreMenuItem target="sidebar-name" icon={ styles }>
				{ __( 'Email styles', 'mailpoet' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name="sidebar-name"
				icon={ styles }
				title={ __( 'Styles', 'mailpoet' ) }
				className="mailpoet-email-editor__styles-panel"
			>
				<StylesSidebarContent />
			</PluginSidebar>
		</>
	);
}
