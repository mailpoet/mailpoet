import { validateForm } from '../form_validator.jsx';

export const changeFormSettings = (state, action) => {
  const newState = {
    ...state,
    formData: {
      ...state.formData,
      settings: action.settings,
    },
  };
  return {
    ...newState,
    hasUnsavedChanges: true,
    formErrors: validateForm(newState.formData, newState.formBlocks),
  };
};
