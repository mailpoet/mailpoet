import { registerCoreBlocks } from '@wordpress/block-library';
import { disableNestedColumns } from './core/column';
import { deactivateStackOnMobile } from './core/columns';

export function initBlocks() {
  disableNestedColumns();
  deactivateStackOnMobile();
  registerCoreBlocks();
}
