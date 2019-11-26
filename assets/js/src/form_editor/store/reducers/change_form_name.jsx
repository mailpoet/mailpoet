export default (state, action) => ({
  ...state,
  formData: {
    ...state.formData,
    name: action.name,
  },
});
