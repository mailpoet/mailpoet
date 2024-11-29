import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';

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

/**
 * A button to the rich text editor to open modal with registered personalization tags.
 */
function PersonalizationTagsButton() {
	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon="shortcode"
					title={ __( 'Personalization Tags', 'mailpoet' ) }
					onClick={ () => {} }
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
