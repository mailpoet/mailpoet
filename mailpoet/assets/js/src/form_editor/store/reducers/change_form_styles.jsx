export const changeFormStyles = (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    styles: action.styles,
    hasUnsavedChanges: true,
  },
});
