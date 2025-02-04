/**
 * External dependencies
 */
import { useEffect, useState, createPortal } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SendButton } from '../components/header/send-button';
import { useContentValidation } from '../hooks';
import { useEntitiesSavedStatesIsDirty } from '@wordpress/editor';
import { editorCurrentPostType } from '../store';

type NextButtonSlotPropType = {
	children: React.ReactNode;
};

function NextPublishSlot({ children }: NextButtonSlotPropType) {
	const [sendButtonPortalEl] = useState(document.createElement('div'));

	// Place element for rendering send button next to publish button
	useEffect(() => {
		const publishButton = document.getElementsByClassName(
			'editor-post-publish-button__button',
		)[0];
		publishButton.parentNode.insertBefore(sendButtonPortalEl, publishButton.nextSibling);
	}, [sendButtonPortalEl]);

	return createPortal(<>{children}</>, sendButtonPortalEl);
}

export function PublishSave() {
	const { validateContent, isInvalid } = useContentValidation();

	const { dirtyEntityRecords } = useEntitiesSavedStatesIsDirty();
	const hasNonEmailEdits = dirtyEntityRecords.some(
		( entity ) => entity.name !== editorCurrentPostType
	);
	useEffect(() => {
		const publishButton = document.getElementsByClassName(
			'editor-post-publish-button__button',
		)[0];
		if (hasNonEmailEdits) {
			publishButton.classList.remove('force-hidden');
		} else {
			publishButton.classList.add('force-hidden');
		}
		// It may get additionally re-rendered by the editor, so we need to check it again
		setTimeout(() => {
			const publishButton = document.getElementsByClassName(
				'editor-post-publish-button__button',
			)[0];
			if (hasNonEmailEdits) {
				publishButton.classList.remove('force-hidden');
			} else {
				publishButton.classList.add('force-hidden');
			}
		}, 200)
	});

	return (
		<NextPublishSlot>
			{!hasNonEmailEdits && <SendButton validateContent={validateContent} isContentInvalid={isInvalid}/>}
		</NextPublishSlot>
	);
}
