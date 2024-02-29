import { registerCoreBlocks } from '@wordpress/block-library';
import { disableNestedColumns, enhanceColumnBlock } from './core/column';
import {
  disableColumnsLayout,
  deactivateStackOnMobile,
  enhanceColumnsBlock,
} from './core/columns';
import { disableImageFilter, hideExpandOnClick } from './core/image';
import { disableCertainRichTextFormats } from './core/rich-text';
import { enhanceButtonBlock } from './core/button';
import { enhanceButtonsBlock } from './core/buttons';

export function initBlocks() {
  disableNestedColumns();
  deactivateStackOnMobile();
  hideExpandOnClick();
  disableImageFilter();
  disableCertainRichTextFormats();
  disableColumnsLayout();
  enhanceButtonBlock();
  enhanceButtonsBlock();
  enhanceColumnBlock();
  enhanceColumnsBlock();
  registerCoreBlocks();
}
