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

const EmailEditor = () => {
	const { setRenderingMode } = useDispatch( editorStore );
	const { __experimentalReceiveCurrentGlobalStylesId } = useDispatch( coreStore );
	const globalStylesId= useSelect( (select) => select( coreStore ).__experimentalGetCurrentGlobalStylesId() );

	// Enforce template-locked mode on start
	useEffect( () => {
		console.log( 'Email Editor set template-locked mode' );
		void setRenderingMode( 'template-locked' );
		void __experimentalReceiveCurrentGlobalStylesId( window.MailPoetEmailEditor.user_theme_post_id );
	}, [globalStylesId] );
	console.log(globalStylesId, 'ee');
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
