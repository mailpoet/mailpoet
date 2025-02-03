/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { initBlocksUnified } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { createStore } from './store';
import { TemplateSelection } from './components/template-select';
import { StylesSidebar } from './unified-editor/styles-sidebar';
import { useEmailCss } from './hooks';

// @ts-expect-error Hotfix for the Powered by MailPoet block @todo load the variable properly
window.mailpoet_cdn_url = window.MailPoetEmailEditor.mailpoet_cdn_url;
const EmailEditor = () => {
	const { setRenderingMode, updateEditorSettings } =
		useDispatch( editorStore );
	// const { __experimentalReceiveCurrentGlobalStylesId } = useDispatch( coreStore );
	const { globalStylesId, editorSettings, editedPostId } = useSelect( ( select ) => {
		return {
			globalStylesId:
				select( coreStore ).__experimentalGetCurrentGlobalStylesId(),
			editorSettings: select( editorStore ).getEditorSettings(),
			editedPostId: select( editorStore ).getCurrentPost().id,
		};
	} );
	const [ styles ] = useEmailCss();

	// Enforce template-locked mode on start
	useEffect( () => {
		console.log( 'Email Editor set template-locked mode' );
		void setRenderingMode( 'template-locked' );
		// void __experimentalReceiveCurrentGlobalStylesId( window.MailPoetEmailEditor.user_theme_post_id );
	}, [ globalStylesId ] );

	// Push email styles to editor settings.
	// Set styles directly to settings overwriting the automatically loaded theme styles
	useEffect( () => {
		if ( ! styles ) {
			return;
		}
		updateEditorSettings( {
			...editorSettings,
			styles,
		} );
	}, [ styles, editedPostId ] );

	return (
		<>
			<TemplateSelection />
			<StylesSidebar />
		</>
	);
};

registerPlugin( 'email-editor-plugin', { render: EmailEditor } );
initBlocksUnified();
initializeLayout();
createStore();
