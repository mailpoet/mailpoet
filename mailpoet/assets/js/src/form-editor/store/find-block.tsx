import { BlockInstance } from '@wordpress/blocks';

export const findBlock = (
  blocks: Array<BlockInstance>,
  name,
): BlockInstance | null =>
  blocks.reduce((result, block) => {
    if (result) {
      return result;
    }
    if (block.name === name) {
      return block;
    }
    if (Array.isArray(block.innerBlocks) && block.innerBlocks.length) {
      return findBlock(block.innerBlocks, name);
    }
    return null;
  }, null);
