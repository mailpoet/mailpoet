import '@wordpress/format-library';
import { registerFormatType } from '@wordpress/rich-text';
import * as FontSelectionFormat from './font_selection_format';

export default function () {
  registerFormatType(FontSelectionFormat.name, FontSelectionFormat.settings);
}
