import { validateForm } from '../form-validator.jsx';

export const changeFormBlocks = (state, action) => {
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
