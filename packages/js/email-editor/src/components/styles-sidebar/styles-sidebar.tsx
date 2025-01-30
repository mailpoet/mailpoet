/**
 * External dependencies
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
import { styles } from '@wordpress/icons';


/**
 * Internal dependencies
 */
import { storeName, stylesSidebarId } from '../../store';
import { StylesSidebarContent} from "./sidebar-content";

type Props = ComponentProps< typeof ComplementaryArea >;

export function RawStylesSidebar( props: Props ): JSX.Element {
	return (
		<ComplementaryArea
			identifier={ stylesSidebarId }
			className="mailpoet-email-editor__styles-panel"
			header={ __( 'Styles', 'mailpoet' ) }
			closeLabel={ __( 'Close styles sidebar', 'mailpoet' ) }
			icon={ styles }
			scope={ storeName }
			smallScreenTitle={ __( 'No title', 'mailpoet' ) }
			{ ...props }
		>
			<StylesSidebarContent />
		</ComplementaryArea>
	);
}

export const StylesSidebar = memo( RawStylesSidebar );
