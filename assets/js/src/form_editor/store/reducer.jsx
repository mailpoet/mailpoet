const addNotice = (state, action) => {
  const notices = state.notices.filter((item) => item.id !== action.id);
  const notice = {
    id: action.id ? action.id : Math.random().toString(36).substr(2, 9),
    content: action.content,
    isDismissible: action.isDismissible,
    status: action.status,
  };
  return {
    ...state,
    notices: [...notices, notice],
  };
};

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
    case 'ADD_NOTICE':
      return addNotice(state, action);
    case 'REMOVE_NOTICE':
      return {
        ...state,
        notices: [...state.notices].filter((item) => item.id !== action.id),
      };
    default:
      return state;
  }
};
