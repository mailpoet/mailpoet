/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { SelectTemplateModal } from './select-modal';
import { useEditorMode } from '../../hooks';

export function TemplateSelection() {
	const [ editorMode ] = useEditorMode();
	const [ templateSelected, setTemplateSelected ] = useState( false );
	const { emailTemplateSlug, emailHasEdits } = useSelect(
		( select ) => ( {
			emailContentIsEmpty: select( storeName ).hasEmptyContent(),
			emailHasEdits: select( storeName ).hasEdits(),
			emailTemplateSlug:
				// @ts-expect-error getEditedPostAttribute accepts one argument TS thinks it accepts zero
				select( editorStore ).getEditedPostAttribute( 'template' ),
		} ),
		[]
	);
	if (
		editorMode === 'template' ||
		emailHasEdits ||
		templateSelected ||
		emailTemplateSlug
	) {
		return null;
	}

	return (
		<SelectTemplateModal
			onSelectCallback={ () => setTemplateSelected( true ) }
		/>
	);
}
