import validateForm from '../form_validator.jsx';

export default (state, action) => {
  const newState = {
    ...state,
    formBlocks: action.blocks,
  };
  return {
    ...newState,
    hasUnsavedChanges: true,
    formErrors: validateForm(newState.formData, newState.formBlocks),
  };
};
