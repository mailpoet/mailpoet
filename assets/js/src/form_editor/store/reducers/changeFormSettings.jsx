export default (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    settings: action.settings,
  },
});
