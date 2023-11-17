import { registerCoreBlocks } from '@wordpress/block-library';
import { disableNestedColumns } from './core/column';
import { disableColumnsLayout, deactivateStackOnMobile } from './core/columns';
import { hideExpandOnClick } from './core/image';
import { disableCertainRichTextFormats } from './core/rich-text';

export function initBlocks() {
  disableNestedColumns();
  deactivateStackOnMobile();
  hideExpandOnClick();
  disableCertainRichTextFormats();
  disableColumnsLayout();
  registerCoreBlocks();
}
