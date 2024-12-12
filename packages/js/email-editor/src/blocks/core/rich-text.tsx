import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createTextToHtmlMap,
	getCursorPosition,
	isMatchingComment,
} from '../../components/personalization-tags/rich-text-utils';
import { PersonalizationTagsModal } from '../../components/personalization-tags/personalization-tags-modal';
import { useCallback, useState } from '@wordpress/element';

/**
 * Disable Rich text formats we currently cannot support
 * Note: This will remove its support for all blocks in the email editor e.g., p, h1,h2, etc
 */
function disableCertainRichTextFormats() {
	// remove support for inline image - We can't use it
	unregisterFormatType( 'core/image' );

	// remove support for Inline code - Not well formatted
	unregisterFormatType( 'core/code' );

	// remove support for Language - Not supported for now
	unregisterFormatType( 'core/language' );
}

type Props = {
	contentRef: React.RefObject< HTMLElement >;
};

/**
 * A button to the rich text editor to open modal with registered personalization tags.
 *
 * @param root0
 * @param root0.contentRef
 */
function PersonalizationTagsButton( { contentRef }: Props ) {
	const [ isModalOpened, setIsModalOpened ] = useState( false );
	const selectedBlockId = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSelectedBlockClientId()
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	// Get the current block content
	const blockContent: string = useSelect( ( select ) => {
		const attributes =
			// @ts-ignore
			select( 'core/block-editor' ).getBlockAttributes( selectedBlockId );
		return attributes?.content?.originalHTML || attributes?.content || ''; // After first saving the content does not have property originalHTML, so we need to check for content as well
	} );

	const handleInsert = useCallback(
		( tag: string ) => {
			const selection =
				contentRef.current.ownerDocument.defaultView.getSelection();
			if ( ! selection ) {
				return;
			}

			const range = selection.getRangeAt( 0 );
			if ( ! range ) {
				return;
			}

			const { mapping } = createTextToHtmlMap( blockContent );
			let { start, end } = getCursorPosition( contentRef, blockContent );

			// If indexes are not matching a comment, update them
			if ( ! isMatchingComment( blockContent, start, end ) ) {
				start = mapping[ start ] ?? blockContent.length;
				end = mapping[ end ] ?? blockContent.length;
			}

			const updatedContent =
				blockContent.slice( 0, start ) +
				`<!--${ tag }-->` +
				blockContent.slice( end );

			updateBlockAttributes( selectedBlockId, {
				content: updatedContent,
			} );
		},
		[ blockContent, contentRef, selectedBlockId, updateBlockAttributes ]
	);

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon="shortcode"
					title={ __( 'Personalization Tags', 'mailpoet' ) }
					onClick={ () => setIsModalOpened( true ) }
				/>
				<PersonalizationTagsModal
					isOpened={ isModalOpened }
					onInsert={ ( value ) => {
						handleInsert( value );
						setIsModalOpened( false );
					} }
					closeCallback={ () => setIsModalOpened( false ) }
				/>
			</ToolbarGroup>
		</BlockControls>
	);
}

/**
 * Extend the rich text formats with a button for personalization tags.
 */
function extendRichTextFormats() {
	registerFormatType( 'mailpoet-email-editor/shortcode', {
		title: __( 'Personalization Tags', 'mailpoet' ),
		className: 'mailpoet-email-editor-personalization-tags',
		tagName: 'span',
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment -- The types does not match
		// @ts-ignore
		attributes: {},
		edit: PersonalizationTagsButton,
	} );
}

export { disableCertainRichTextFormats, extendRichTextFormats };
