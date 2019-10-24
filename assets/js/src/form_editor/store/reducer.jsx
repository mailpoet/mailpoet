export default (defaultState) => (state = defaultState, action) => {
  switch (action.type) {
    case 'TOGGLE_SIDEBAR':
      return {
        ...state,
        sidebarOpened: action.toggleTo,
      };
    case 'CHANGE_FORM_NAME':
      return {
        ...state,
        formData: {
          ...state.formData,
          name: action.name,
        },
      };
    case 'SAVE_FORM_STARTED':
      return {
        ...state,
        isFormSaving: true,
      };
    case 'SAVE_FORM_DONE':
      return {
        ...state,
        isFormSaving: false,
      };
    default:
      return state;
  }
};
