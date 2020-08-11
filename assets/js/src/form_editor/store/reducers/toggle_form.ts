export const enableForm = (state) => ({
  ...state,
  formData: {
    ...state.formData,
    hasUnsavedChanges: true,
    status: 'enabled',
  },
});

export const disableForm = (state) => ({
  ...state,
  formData: {
    ...state.formData,
    hasUnsavedChanges: true,
    status: 'disabled',
  },
});
