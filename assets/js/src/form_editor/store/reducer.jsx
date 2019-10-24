const DEFAULT_STATE = {
  sidebarOpened: true,
};

export default (state = DEFAULT_STATE, action) => {
  switch (action.type) {
    case 'TOGGLE_SIDEBAR':
      return {
        ...state,
        sidebarOpened: action.toggleTo,
      };
    default:
      return state;
  }
};
