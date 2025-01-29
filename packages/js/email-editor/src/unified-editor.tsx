import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

const EmailEditor = () => {
	const { setRenderingMode } = useDispatch( editorStore );

	// Enforce template-locked mode on start
	useEffect( () => {
		console.log( 'Email Editor set template-locked mode' );
		void setRenderingMode( 'template-locked' );
	}, [] );
	return null;
};

registerPlugin( 'email-editor-plugin', { render: EmailEditor } );
