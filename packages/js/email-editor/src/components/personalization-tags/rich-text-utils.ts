import * as React from '@wordpress/element';
import { PersonalizationTag } from '../../store';

/**
 * Maps indices of characters in HTML representation of the value to corresponding characters of stored value in RichText content. The stored value doesn't contain tags.
 * This function skips over HTML tags, only mapping visible text content.
 *
 *
 * @param {string} html - The HTML string to map. Example: 'Hello <span contenteditable="false" data-rich-text-comment="[user/firstname]"><span>[user/firstname]</span></span>!'
 * @return {number[]} - A mapping array where each HTML index points to its corresponding stored value index.
 */
const mapRichTextToValue = ( html: string ) => {
	const mapping = []; // Maps HTML indices to stored value indices
	let htmlIndex = 0;
	let valueIndex = 0;
	let isInsideTag = false;

	while ( htmlIndex < html.length ) {
		const htmlChar = html[ htmlIndex ];
		if ( htmlChar === '<' ) {
			isInsideTag = true; // Entering an HTML tag
		}
		if ( htmlChar === '>' ) {
			isInsideTag = false; // Exiting an HTML tag
		}
		mapping[ htmlIndex ] = valueIndex;
		if ( ! isInsideTag ) {
			valueIndex++; // Increment value index only for visible text
		}

		htmlIndex++;
	}

	return mapping;
};

/**
 * Creates a mapping between plain text indices and corresponding HTML indices.
 * This includes handling of HTML comments, ensuring text is mapped correctly.
 * We need to this step because the text displayed to the user is different from the HTML content.
 *
 * @param {string} html - The HTML string to map. Example: 'Hello, <!--[user/firstname]-->!'
 * @return {{ mapping: number[] }} - An object containing the mapping array.
 */
const createTextToHtmlMap = ( html: string ) => {
	const text = [];
	const mapping = [];
	let isInsideComment = false;

	for ( let i = 0; i < html.length; i++ ) {
		const char = html[ i ];

		// Detect start of an HTML comment
		if ( ! isInsideComment && html.slice( i, i + 4 ) === '<!--' ) {
			i += 4; // Skip the start of the comment
			isInsideComment = true;
		}

		// Detect end of an HTML comment
		if ( isInsideComment && html.slice( i, i + 3 ) === '-->' ) {
			i += 3; // Skip the end of the comment
			isInsideComment = false;
		}

		text.push( char );
		mapping[ text.length - 1 ] = i; // Map text index to HTML index
	}

	// Ensure the mapping includes the end of the content
	if (
		mapping.length === 0 ||
		mapping[ mapping.length - 1 ] !== html.length
	) {
		mapping[ text.length ] = html.length; // Map end of content
	}

	return { mapping };
};

/**
 * Retrieves the cursor position within a RichText component.
 * Calculates the offset in plain text while accounting for HTML tags and comments.
 *
 * @param {React.RefObject<HTMLElement>} richTextRef - Reference to the RichText component.
 * @param {string}                       content     - The plain text content of the block.
 * @return {{ start: number, end: number } | null} - The cursor position as start and end offsets.
 */
const getCursorPosition = (
	richTextRef: React.RefObject< HTMLElement >,
	content: string
): { start: number; end: number } => {
	const selection =
		richTextRef.current.ownerDocument.defaultView.getSelection();

	if ( ! selection.rangeCount ) {
		return null; // No selection present
	}

	const range = selection.getRangeAt( 0 );
	const container = range.startContainer;

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
				offset += currentNode.textContent.length; // Add text content length of siblings
			}
			currentNode = currentNode.parentNode;
		}
	} else {
		// Locate the selected content in the HTML
		const htmlContent = richTextRef.current.innerHTML;
		const selectedText = range.toString();

		// The selected text is wrapped by span and it is also in the HTML attribute. Adding brackets helps to find the correct index.
		// After that we need increase the index by 5 to get the correct index. (4 for start of the HTML comment and 1 for the first bracket)
		const startIndex =
			htmlContent.indexOf( `>${ selectedText }<`, offset ) + 5;

		// Map the startIndex to the stored value
		const mapping = mapRichTextToValue( htmlContent );
		const translatedOffset = mapping[ startIndex ] || 0;

		// Search for HTML comments within the stored value
		const htmlCommentRegex = /<!--\[(.*?)\]-->/g;
		let match;
		let commentStart = -1;
		let commentEnd = -1;

		while ( ( match = htmlCommentRegex.exec( content ) ) !== null ) {
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

		// Return comment range if found
		if ( commentStart !== -1 && commentEnd !== -1 ) {
			return { start: commentStart, end: commentEnd };
		}
	}

	// Default to the current offset if no comment is found
	return {
		start: offset,
		end: offset + range.toString().length,
	};
};

/**
 * Determines if a given substring within content matches an HTML comment.
 *
 * @param {string} content - The full content string to search.
 * @param {number} start   - The start index of the substring.
 * @param {number} end     - The end index of the substring.
 * @return {boolean} - True if the substring matches an HTML comment, otherwise false.
 */
const isMatchingComment = (
	content: string,
	start: number,
	end: number
): boolean => {
	const substring = content.slice( start, end );

	// Regular expression to match HTML comments
	const htmlCommentRegex = /^<!--([\s\S]*?)-->$/;

	return htmlCommentRegex.test( substring );
};

/**
 * Replace registered personalization tags with HTML comments in content.
 * @param content string The content to replace the tags in.
 * @param tags    PersonalizationTag[] The tags to replace in the content.
 */
const replacePersonalizationTagsWithHTMLComments = (
	content: string,
	tags: PersonalizationTag[]
) => {
	tags.forEach( ( tag ) => {
		if ( ! content.includes( tag.token ) ) {
			// Skip if the token is not in the content
			return;
		}

		const escapedRegExp = tag.token.replace(
			/[.*+?^${}()|[\]\\]/g,
			'\\$&'
		); // Escape special characters
		const regex = new RegExp( `(?<!<!--)${ escapedRegExp }(?!-->)`, 'g' ); // Match token not inside HTML comments
		content = content.replace( regex, `<!--${ tag.token }-->` );
	} );
	return content;
};

export {
	isMatchingComment,
	getCursorPosition,
	createTextToHtmlMap,
	mapRichTextToValue,
	replacePersonalizationTagsWithHTMLComments,
};
