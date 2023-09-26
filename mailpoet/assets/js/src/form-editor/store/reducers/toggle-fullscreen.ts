export const toggleFullscreen = (state, action) => ({
  ...state,
  fullscreenStatus: action.toggleTo,
});
