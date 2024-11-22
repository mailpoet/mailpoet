import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { InnerEditor } from './components/block-editor';
import { createStore, storeName } from './store';
import { initHooks } from './editor-hooks';
import { KeyboardShortcuts } from './components/keybord-shortcuts';
import './index.scss';

function Editor() {
	const { postId, settings } = useSelect(
		( select ) => ( {
			postId: select( storeName ).getEmailPostId(),
			settings: select( storeName ).getInitialEditorSettings(),
		} ),
		[]
	);

	return (
		<StrictMode>
			<KeyboardShortcuts />
			<InnerEditor
				initialEdits={ [] }
				postId={ postId }
				postType="mailpoet_email"
				settings={ settings }
			/>
		</StrictMode>
	);
}

const WrappedEditor = applyFilters(
	'mailpoet_email_editor_wrap_editor_component',
	Editor
);

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	createStore();
	initializeLayout();
	initBlocks();
	initHooks();
	const root = createRoot( container );
	// @ts-ignore We don't have a type for WrappedEditor.
	root.render( <WrappedEditor /> );
}
