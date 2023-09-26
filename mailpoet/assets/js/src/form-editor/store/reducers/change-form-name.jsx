export const changeFormName = (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    hasUnsavedChanges: true,
    name: action.name,
  },
});
