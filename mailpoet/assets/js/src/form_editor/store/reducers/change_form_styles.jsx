export default (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    styles: action.styles,
    hasUnsavedChanges: true,
  },
});
