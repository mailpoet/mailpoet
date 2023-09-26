export const customFieldDeleteStart = (state) => {
  const notices = state.notices.filter(
    (notice) => notice.id !== 'custom-field',
  );
  return {
    ...state,
    ...notices,
    isCustomFieldDeleting: true,
  };
};

export const customFieldDeleteFailed = (state, action) => {
  const notices = state.notices.filter(
    (notice) => notice.id !== 'custom-field',
  );
  notices.push({
    id: 'custom-field',
    content: action.message,
    isDismissible: true,
    status: 'error',
  });
  return {
    ...state,
    isCustomFieldSaving: false,
    notices,
  };
};

export const customFieldDeleteDone = (state, action) => {
  const customFields = state.customFields.filter(
    (customField) => customField.id !== action.customFieldId,
  );

  const formBlocks = state.formBlocks.filter(
    (block) => block.clientId !== action.clientId,
  );

  return {
    ...state,
    formBlocks,
    isCustomFieldDeleting: false,
    customFields,
  };
};
