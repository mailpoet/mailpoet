import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { storeName } from '../../store';
import { useSelect, useDispatch } from '@wordpress/data';

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
	isActive: boolean;
	value: string;
	onChange: ( value: string ) => void;
	contentRef: React.RefObject< HTMLElement >;
};

/**
 * A button to the rich text editor to open modal with registered personalization tags.
 *
 * @param root0
 * @param root0.contentRef
 */
function PersonalizationTagsButton( { contentRef }: Props ) {
	const { togglePersonalizationTagsModal } = useDispatch( storeName );

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

	// Convert `RichText` DOM offset to stored value offset
	const mapRichTextToValue = ( html ) => {
		const mapping = []; // Maps HTML indices to stored value indices
		let htmlIndex = 0;
		let valueIndex = 0;
		let isInsideTag = false;

		while ( htmlIndex < html.length ) {
			const htmlChar = html[ htmlIndex ];
			if ( htmlChar === '<' ) {
				isInsideTag = true;
			}
			if ( htmlChar === '>' ) {
				isInsideTag = false;
			}
			mapping[ htmlIndex ] = valueIndex;
			if ( ! isInsideTag ) {
				valueIndex++;
			}

			htmlIndex++;
		}

		return mapping;
	};

	const createTextToHtmlMap = ( html ) => {
		const text = [];
		const mapping = [];
		let isInsideComment = false;

		for ( let i = 0; i < html.length; i++ ) {
			const char = html[ i ];

			// Detect start of an HTML comment
			if ( ! isInsideComment && html.slice( i, i + 4 ) === '<!--' ) {
				i += 4; // Adjust loop
				isInsideComment = true;
			}

			// Detect end of an HTML comment
			if ( isInsideComment && html.slice( i, i + 3 ) === '-->' ) {
				i += 3; // Adjust loop
				isInsideComment = false;
			}

			text.push( char );
			mapping[ text.length - 1 ] = i;
		}

		// Append mapping for positions between adjacent comments
		if (
			mapping.length === 0 ||
			mapping[ mapping.length - 1 ] !== html.length
		) {
			mapping[ text.length ] = html.length; // Map end of content
		}

		return { mapping };
	};

	const getCursorPosition = ( richTextRef ) => {
		const selection =
			richTextRef.current.ownerDocument.defaultView.getSelection();

		if ( ! selection.rangeCount ) {
			return null;
		}

		const range = selection.getRangeAt( 0 );
		const container = range.startContainer;
		const currentValue = blockContent;

		// Ensure the selection is within the RichText component
		if ( ! richTextRef.current.contains( container ) ) {
			return null;
		}

		let offset = range.startOffset; // Initial offset within the current node
		let currentNode = container;

		// Traverse the DOM tree to calculate the total offset
		if ( currentNode !== richTextRef.current ) {
			while ( currentNode && currentNode !== richTextRef.current ) {
				while ( currentNode.previousSibling ) {
					currentNode = currentNode.previousSibling;
					offset += currentNode.textContent.length;
				}
				currentNode = currentNode.parentNode;
			}
		} else {
			// Locate the selected content in the HTML
			const htmlContent = richTextRef.current.innerHTML;
			const selectedText = range.toString();
			const startIndex = htmlContent.indexOf( selectedText, offset );
			const mapping = mapRichTextToValue( htmlContent );

			// Translate `offset` from `RichText` HTML to stored value
			const translatedOffset = mapping[ startIndex ] || 0;

			// Search for the HTML comment in the stored value
			const htmlCommentRegex = /<!--\[(.*?)\]-->/g;
			let match;
			let commentStart = -1;
			let commentEnd = -1;

			while (
				( match = htmlCommentRegex.exec( currentValue ) ) !== null
			) {
				const [ fullMatch ] = match;
				const matchStartIndex = match.index;
				const matchEndIndex = matchStartIndex + fullMatch.length;

				if (
					translatedOffset >= matchStartIndex &&
					translatedOffset <= matchEndIndex
				) {
					commentStart = matchStartIndex;
					commentEnd = matchEndIndex;
					break;
				}
			}
			// If a comment is detected, return its range
			if ( commentStart !== -1 && commentEnd !== -1 ) {
				return {
					start: commentStart,
					end: commentEnd,
				};
			}
		}

		return {
			start: Math.min( offset, currentValue.length ),
			end: Math.min(
				offset + range.toString().length,
				currentValue.length
			),
		};
	};

	const isMatchingComment = ( content, start, end ): boolean => {
		// Extract the substring
		const substring = content.slice( start, end );

		// Define the regex for HTML comments
		const htmlCommentRegex = /^<!--(.*?)-->$/;

		// Test if the substring matches the regex
		const match = htmlCommentRegex.exec( substring );

		if ( match ) {
			return true;
		}

		return false;
	};

	const handleInsert = ( tag: string ) => {
		const selection =
			contentRef.current.ownerDocument.defaultView.getSelection();
		if ( ! selection ) {
			return;
		}

		const range = selection.getRangeAt( 0 );
		if ( ! range ) {
			return;
		}

		// Generate text-to-HTML mapping
		const { mapping } = createTextToHtmlMap( blockContent );

		// Ensure selection range is within bounds
		const selectionRange = getCursorPosition( contentRef );
		const start = selectionRange.start;
		const end = selectionRange.end;

		// Default values for starting and ending indexes.
		let htmlStart = start;
		let htmlEnd = end;
		// If indexes are not matching a comment, update them
		if ( ! isMatchingComment( blockContent, htmlStart, htmlEnd ) ) {
			htmlStart = mapping[ start ] ?? blockContent.length;
			htmlEnd = mapping[ end ] ?? blockContent.length;
		}

		const updatedContent =
			blockContent.slice( 0, htmlStart ) +
			`<!--${ tag }-->` +
			blockContent.slice( htmlEnd );

		updateBlockAttributes( selectedBlockId, { content: updatedContent } );
	};

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon="shortcode"
					title={ __( 'Personalization Tags', 'mailpoet' ) }
					onClick={ () => {
						togglePersonalizationTagsModal( true, {
							onInsert: handleInsert,
						} );
					} }
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
