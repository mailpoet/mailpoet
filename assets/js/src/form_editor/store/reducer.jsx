export default (defaultState) => (state = defaultState, action) => {
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
