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
import { initBlocksUnified } from './blocks';
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
import { useEmailCss } from './hooks';
import { initEventCollector } from './events';

import './unified-editor/styles.scss';

// @ts-expect-error Hotfix for the Powered by MailPoet block @todo load the variable properly
window.mailpoet_cdn_url = window.MailPoetEmailEditor.mailpoet_cdn_url;

const EmailEditor = () => {
	// @ts-expect-error Missing types for setRenderingMode and removeEditorPanel
	const { setRenderingMode, updateEditorSettings, removeEditorPanel } =
		useDispatch( editorStore );
	const { editedPostId, emailTemplateSlug } = useSelect( ( sel ) => {
		return {
			editedPostId: sel( editorStore ).getCurrentPost().id,
			emailTemplateSlug:
				// @ts-expect-error getEditedPostAttribute accepts one argument TS thinks it accepts zero
				select( editorStore ).getEditedPostAttribute( 'template' ),
		};
	} );
	const [ styles ] = useEmailCss();

	// Enforce template-locked mode on start
	useEffect( () => {
		if ( emailTemplateSlug ) {
			void setRenderingMode( 'template-locked' );
		}
		void removeEditorPanel( 'post-status' ); // Hide default post status panel
	}, [ emailTemplateSlug, removeEditorPanel, setRenderingMode ] );

	// Push email styles to editor settings.
	// Set styles directly to settings overwriting the automatically loaded theme styles
	useEffect( () => {
		if ( ! styles ) {
			return;
		}
		const editorSettings = select( editorStore ).getEditorSettings();
		updateEditorSettings( {
			...editorSettings,
			styles,
		} );
	}, [ styles, editedPostId, updateEditorSettings ] );

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

registerPlugin( 'email-editor-plugin', { render: EmailEditor } );
initBlocksUnified();
initializeLayout();
createStore();
initEventCollector();
