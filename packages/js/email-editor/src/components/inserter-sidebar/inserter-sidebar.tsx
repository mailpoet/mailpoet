import {
	__experimentalLibrary as Library, // eslint-disable-line
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

export function InserterSidebar() {
	const { postContentId, isEditingEmailContent } = useSelect( ( select ) => {
		const blocks = select( blockEditorStore ).getBlocks();
		return {
			postContentId: blocks.find(
				( block ) => block.name === 'core/post-content'
			)?.clientId,
			isEditingEmailContent:
				select( editorStore ).getCurrentPostType() !== 'wp_template',
		};
	} );

	// @ts-expect-error missing types.
	const { setIsInserterOpened } = useDispatch( editorStore );

	return (
		<div className="editor-inserter-sidebar">
			<div className="editor-inserter-sidebar__content">
				<Library
					showMostUsedBlocks
					showInserterHelpPanel={ false }
					// In the email content mode we insert primarily into the post content block.
					rootClientId={
						isEditingEmailContent ? postContentId : null
					}
					onClose={ () => setIsInserterOpened( false ) }
				/>
			</div>
		</div>
	);
}
