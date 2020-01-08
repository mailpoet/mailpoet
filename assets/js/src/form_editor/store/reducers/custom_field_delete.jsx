export const customFieldDeleteCancel = (state) => ({
  ...state,
  displayCustomFieldDeleteConfirm: false,
});

export const customFieldDeleteClick = (state) => ({
  ...state,
  displayCustomFieldDeleteConfirm: true,
});
