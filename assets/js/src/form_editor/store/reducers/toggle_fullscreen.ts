export default (state, action) => ({
  ...state,
  fullscreenStatus: action.toggleTo,
});
