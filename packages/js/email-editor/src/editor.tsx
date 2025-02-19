/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import '@wordpress/format-library'; // Enables text formatting capabilities

/**
 * Internal dependencies
 */
import { initEmailModifications } from './unified-editor';
import { storeName, editorCurrentPostType } from './store';
import { InnerEditor } from './components/block-editor';
import './index.scss';

function EmailEditor() {
	const { postId, settings } = useSelect(
		( select ) => ( {
			postId: select( storeName ).getEmailPostId(),
			settings: select( storeName ).getInitialEditorSettings(),
		} ),
		[]
	);

	return (
		<StrictMode>
			<InnerEditor
				postId={ postId }
				postType={ editorCurrentPostType }
				settings={ settings }
			/>
		</StrictMode>
	);
}

const WrappedEditor = applyFilters(
	'mailpoet_email_editor_wrap_editor_component',
	EmailEditor
) as typeof EmailEditor;

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	initEmailModifications();
	// initEventCollector();
	// createStore();
	// initializeLayout();
	// initBlocks();
	// initHooks();
	const root = createRoot( container );
	root.render( <WrappedEditor /> );
}
