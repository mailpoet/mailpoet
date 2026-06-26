import { validateForm } from '../form-validator.jsx';
import { normalizePadding } from '../normalize-padding';

const normalizeBlockPadding = (block) => {
  const normalizedBlock = { ...block };
  if (Array.isArray(block.innerBlocks)) {
    normalizedBlock.innerBlocks = block.innerBlocks.map(normalizeBlockPadding);
  }

  const padding = block.attributes?.style?.spacing?.padding;
  if (padding === undefined) {
    return normalizedBlock;
  }

  return {
    ...normalizedBlock,
    attributes: {
      ...block.attributes,
      style: {
        ...block.attributes.style,
        spacing: {
          ...block.attributes.style.spacing,
          padding: normalizePadding(padding),
        },
      },
    },
  };
};

export const changeFormBlocks = (state, action) => {
  const newState = {
    ...state,
    formBlocks: action.blocks.map(normalizeBlockPadding),
  };
  return {
    ...newState,
    hasUnsavedChanges: true,
    formErrors: validateForm(newState.formData, newState.formBlocks),
  };
};
