/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import * as React from '@wordpress/element';

/**
 * WordPress private dependencies
 */
import { Tabs } from '../../private-apis';

/**
 * Internal dependencies
 */
import { mainSidebarDocumentTab, mainSidebarBlockTab } from '../../store';

export function HeaderTabs( _, ref ) {
	return (
		<Tabs.TabList ref={ ref }>
			<Tabs.Tab tabId={ mainSidebarDocumentTab }>
				{ __( 'Email', 'mailpoet' ) }
			</Tabs.Tab>
			<Tabs.Tab tabId={ mainSidebarBlockTab }>{ __( 'Block' ) }</Tabs.Tab>
		</Tabs.TabList>
	);
}

export const Header = React.forwardRef( HeaderTabs );
