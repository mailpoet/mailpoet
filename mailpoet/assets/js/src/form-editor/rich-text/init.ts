import '@wordpress/format-library'; // load default formats (bold, italic, ...)
import { registerFormatType } from '@wordpress/rich-text';
import * as FontSelectionFormat from './font-selection-format';

export function initRichText(): void {
  registerFormatType(
    FontSelectionFormat.name,
    FontSelectionFormat.settings as unknown as Parameters<
      typeof registerFormatType
    >[1],
  );
}
