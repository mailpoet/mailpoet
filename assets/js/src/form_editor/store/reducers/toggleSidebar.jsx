export default (state, action) => ({
  ...state,
  sidebarOpened: action.toggleTo,
});
