export const customFieldDeleteCancel = (state) => ({
  ...state,
  displayCustomFieldDeleteConfirm: false,
});

export const customFieldDeleteClick = (state) => ({
  ...state,
  displayCustomFieldDeleteConfirm: true,
});

export const customFieldDeleteStart = (state) => ({
  ...state,
  displayCustomFieldDeleteConfirm: false,
  isCustomFieldDeleting: true,
});


export const customFieldDeleteDone = (state, action) => {
  const customFields = state
    .customFields
    .filter((customField) => customField.id !== action.customFieldId);

  const formBlocks = state
    .formBlocks
    .filter((block) => block.clientId !== action.clientId);
  console.log('blocks', formBlocks);
  return {
    ...state,
    formBlocks,
    isCustomFieldDeleting: false,
    customFields,
  };
};
