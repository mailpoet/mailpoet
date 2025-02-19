/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { createStore } from './store';
import { TemplateSelection } from './components/template-select';
import { StylesSidebar } from './unified-editor/styles-sidebar';
import { SendPreview } from './unified-editor/preview';
import {
	DocumentSettings,
	TemplatePanel,
} from './unified-editor/document-setting';
import { PublishSave } from './unified-editor/publish-save';
import { initEventCollector } from './events';

import './unified-editor/styles.scss';

const EmailEditor = () => {
	// @ts-expect-error Missing types for setRenderingMode and removeEditorPanel
	const { setRenderingMode, removeEditorPanel } = useDispatch( editorStore );
	const { emailTemplateSlug } = useSelect( ( sel ) => {
		return {
			editedPostId: sel( editorStore ).getCurrentPost().id,
			emailTemplateSlug:
				// @ts-expect-error getEditedPostAttribute accepts one argument TS thinks it accepts zero
				select( editorStore ).getEditedPostAttribute( 'template' ),
		};
	} );

	// Enforce template-locked mode on start
	useEffect( () => {
		if ( emailTemplateSlug ) {
			void setRenderingMode( 'template-locked' );
		}
		void removeEditorPanel( 'post-status' ); // Hide default post status panel
	}, [ emailTemplateSlug, removeEditorPanel, setRenderingMode ] );

	return (
		<>
			<TemplateSelection />
			<StylesSidebar />
			<SendPreview />
			<DocumentSettings />
			<TemplatePanel />
			<PublishSave />
		</>
	);
};

export function initEmailModifications() {
	registerPlugin( 'email-editor-plugin', { render: EmailEditor } );
	initBlocks();
	initializeLayout();
	createStore();
	initEventCollector();
}
