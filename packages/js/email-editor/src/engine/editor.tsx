import '@wordpress/format-library'; // Enables text formatting capabilities
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
// import { withNpsPoll } from '../../nps-poll'; // TODO: need to fix this import. custom component from core MP plugin
import { initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { InnerEditor } from './components/block-editor/editor';
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
	root.render( <Editor /> );
}
