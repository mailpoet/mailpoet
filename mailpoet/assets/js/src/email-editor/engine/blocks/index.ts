import { registerCoreBlocks } from '@wordpress/block-library';
import { disableNestedColumns } from './core/column';
import { deactivateStackOnMobile } from './core/columns';
import { disableCertainRichTextFormats } from './core/rich-text';

export function initBlocks() {
  disableNestedColumns();
  deactivateStackOnMobile();
  disableCertainRichTextFormats();
  registerCoreBlocks();
}
