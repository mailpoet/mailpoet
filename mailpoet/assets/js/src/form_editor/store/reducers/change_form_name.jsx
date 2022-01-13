export default (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    hasUnsavedChanges: true,
    name: action.name,
  },
});
