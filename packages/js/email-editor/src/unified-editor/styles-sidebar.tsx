/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { styles } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { StylesSidebarContent } from '../components/styles-sidebar';

export function StylesSidebar() {
	return (
		<>
			<PluginSidebarMoreMenuItem target="sidebar-name" icon={ styles }>
				{ __( 'Email styles' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar name="sidebar-name" icon={ styles } title="Styles">
				<StylesSidebarContent />
			</PluginSidebar>
		</>
	);
}
