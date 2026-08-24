import { validateForm } from '../form-validator.jsx';
import { normalizePadding } from '../normalize-padding';

// Maps items but returns the original array reference when nothing changed.
// Preserving references lets BlockEditorProvider's useBlockSync recognize the
// round-tripped value; otherwise it treats every edit as an external change and
// calls resetBlocks(), which clears the selection of nested blocks.
const mapStable = (items, mapItem) => {
  let changed = false;
  const mapped = items.map((item) => {
    const next = mapItem(item);
    if (next !== item) {
      changed = true;
    }
    return next;
  });
  return changed ? mapped : items;
};

const normalizeBlockPadding = (block) => {
  const innerBlocks = Array.isArray(block.innerBlocks)
    ? mapStable(block.innerBlocks, normalizeBlockPadding)
    : block.innerBlocks;

  const padding = block.attributes?.style?.spacing?.padding;
  const normalizedPadding =
    padding === undefined ? padding : normalizePadding(padding);

  const innerBlocksChanged = innerBlocks !== block.innerBlocks;
  const paddingChanged = normalizedPadding !== padding;

  if (!innerBlocksChanged && !paddingChanged) {
    return block;
  }

  const normalizedBlock = { ...block };
  if (innerBlocksChanged) {
    normalizedBlock.innerBlocks = innerBlocks;
  }
  if (paddingChanged) {
    normalizedBlock.attributes = {
      ...block.attributes,
      style: {
        ...block.attributes.style,
        spacing: {
          ...block.attributes.style.spacing,
          padding: normalizedPadding,
        },
      },
    };
  }
  return normalizedBlock;
};

export const changeFormBlocks = (state, action) => {
  const newState = {
    ...state,
    formBlocks: mapStable(action.blocks, normalizeBlockPadding),
  };
  return {
    ...newState,
    hasUnsavedChanges: true,
    formErrors: validateForm(
      newState.formData,
      newState.formBlocks,
      newState.trackingConsentCaptureEnabled,
    ),
  };
};
