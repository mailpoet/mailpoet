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
import { useEditorMode } from '../../hooks';

export function HeaderTabs( _, ref ) {
	const [ editorMode ] = useEditorMode();
	return (
		<Tabs.TabList ref={ ref }>
			<Tabs.Tab tabId={ mainSidebarDocumentTab }>
				{ editorMode === 'template'
					? __( 'Template', 'mailpoet' )
					: __( 'Email', 'mailpoet' ) }
			</Tabs.Tab>
			<Tabs.Tab tabId={ mainSidebarBlockTab }>{ __( 'Block' ) }</Tabs.Tab>
		</Tabs.TabList>
	);
}

export const Header = React.forwardRef( HeaderTabs );
