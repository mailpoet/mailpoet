import { unregisterFormatType } from '@wordpress/rich-text';

/**
 * Disable Rich text formats we currently cannot support
 * Note: This will remove its support for all blocks in the email editor e.g., p, h1,h2, etc
 */
function disableCertainRichTextFormats() {
  // remove support for inline image - We can't use it
  unregisterFormatType('core/image');

  // remove support for Inline code - Not well formatted
  unregisterFormatType('core/code');

  // remove support for Language - Not supported for now
  unregisterFormatType('core/language');
}

export { disableCertainRichTextFormats };
