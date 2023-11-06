import { registerCoreBlocks } from '@wordpress/block-library';
import { disableNestedColumns } from './core/column';

export function initBlocks() {
  disableNestedColumns();
  registerCoreBlocks();
}
