const findBlock = (blocks, name) =>
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

export default findBlock;
