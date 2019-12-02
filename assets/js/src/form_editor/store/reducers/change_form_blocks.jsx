import validateForm from '../form_validator.jsx';

export default (state, action) => {
  const newState = {
    ...state,
    formBlocks: action.blocks,
  };
  return {
    ...newState,
    formErrors: validateForm(newState.formData, newState.formBlocks),
  };
};
