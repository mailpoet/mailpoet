import { BlockSelectionClearer } from '@wordpress/block-editor';
import { UnsavedChangesWarning } from '@wordpress/editor';
import { uploadMedia } from '@wordpress/media-utils';
import classnames from 'classnames';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ComplementaryArea,
	FullscreenMode,
	InterfaceSkeleton,
} from '@wordpress/interface';
import { useRef } from '@wordpress/element';

import './index.scss';
import { store as coreStore } from '@wordpress/core-data';
import { storeName } from '../../store';
import { useEmailCss } from '../../hooks';
import { AutosaveMonitor } from '../autosave';
import { BlockCompatibilityWarnings, Sidebar } from '../sidebar';
import { Header } from '../header';
import { ListviewSidebar } from '../listview-sidebar/listview-sidebar';
import { InserterSidebar } from '../inserter-sidebar/inserter-sidebar';
import { EditorNotices, SentEmailNotice } from '../notices';
import { StylesSidebar } from '../styles-sidebar';
import { VisualEditor } from './visual-editor/visual-editor';

import { TemplateSelection } from '../template-select';

export function Layout() {
	const {
		isFullscreenActive,
		isSidebarOpened,
		initialSettings,
		previewDeviceType,
		isInserterSidebarOpened,
		isListviewSidebarOpened,
		canUserEditMedia,
		hasFixedToolbar,
		focusMode,
		styles,
	} = useSelect(
		( select ) => ( {
			isFullscreenActive:
				select( storeName ).isFeatureActive( 'fullscreenMode' ),
			isSidebarOpened: select( storeName ).isSidebarOpened(),
			isInserterSidebarOpened:
				select( storeName ).isInserterSidebarOpened(),
			isListviewSidebarOpened:
				select( storeName ).isListviewSidebarOpened(),
			initialSettings: select( storeName ).getInitialEditorSettings(),
			previewDeviceType: select( storeName ).getDeviceType(),
			canUserEditMedia: select( coreStore ).canUser( 'create', 'media' ),
			hasFixedToolbar:
				select( storeName ).isFeatureActive( 'fixedToolbar' ),
			focusMode: select( storeName ).isFeatureActive( 'focusMode' ),
			styles: select( storeName ).getStyles(),
		} ),
		[]
	);

	const { toggleInserterSidebar } = useDispatch( storeName );

	const [ emailCss ] = useEmailCss();
	const className = classnames( 'edit-post-layout', {
		'is-sidebar-opened': isSidebarOpened,
	} );

	const contentRef = useRef( null );

	// Styles for the canvas. Based on template-canvas.php, this equates to the body element.
	const canvasStyles = {
		background:
			previewDeviceType === 'Desktop'
				? styles.color.background
				: 'transparent',
		fontFamily: styles.typography.fontFamily,
		transition: 'all 0.3s ease 0s',
	};

	const settings = {
		...initialSettings,
		mediaUpload: canUserEditMedia ? uploadMedia : null,
		hasFixedToolbar,
		focusMode,
	};

	return (
		<>
			<FullscreenMode isActive={ isFullscreenActive } />
			<UnsavedChangesWarning />
			<AutosaveMonitor />
			<SentEmailNotice />
			<Sidebar />
			<StylesSidebar />
			<TemplateSelection />
			<InterfaceSkeleton
				className={ className }
				header={ <Header /> }
				editorNotices={ <EditorNotices /> }
				content={
					<>
						<EditorNotices />
						<BlockSelectionClearer
							className="edit-post-visual-editor"
							style={ canvasStyles }
							onClick={ () => {
								// Clear inserter sidebar when canvas is clicked.
								if ( isInserterSidebarOpened ) {
									void toggleInserterSidebar();
								}
							} }
						>
							<div className="visual-editor__email_content_wrapper">
								<VisualEditor
									disableIframe={ false }
									styles={ [
										...settings.styles,
										...emailCss,
									] }
									className="has-global-padding"
									contentRef={ contentRef }
									iframeProps={ {} }
								/>
							</div>
						</BlockSelectionClearer>
					</>
				}
				sidebar={ <ComplementaryArea.Slot scope={ storeName } /> }
				secondarySidebar={
					( isInserterSidebarOpened && <InserterSidebar /> ) ||
					( isListviewSidebarOpened && <ListviewSidebar /> )
				}
			/>
			{ /* Rendering Warning component here ensures that the warning is displayed under the border configuration. */ }
			<BlockCompatibilityWarnings />
		</>
	);
}
