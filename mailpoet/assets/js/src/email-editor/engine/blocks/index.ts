import { registerCoreBlocks } from '@wordpress/block-library';
import { enhanceColumnBlock } from './core/column';
import {
  disableColumnsLayout,
  deactivateStackOnMobile,
  enhanceColumnsBlock,
} from './core/columns';
import { enhancePostContentBlock } from './core/post-content';
import { disableImageFilter, hideExpandOnClick } from './core/image';
import { disableCertainRichTextFormats } from './core/rich-text';
import { enhanceButtonBlock } from './core/button';
import { enhanceButtonsBlock } from './core/buttons';
import { alterSupportConfiguration } from './core/general-block-support';

export function initBlocks() {
  deactivateStackOnMobile();
  hideExpandOnClick();
  disableImageFilter();
  disableCertainRichTextFormats();
  disableColumnsLayout();
  enhanceButtonBlock();
  enhanceButtonsBlock();
  enhanceColumnBlock();
  enhanceColumnsBlock();
  enhancePostContentBlock();
  alterSupportConfiguration();
  registerCoreBlocks();
}
