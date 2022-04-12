export const switchDefaultSidebarTab = (state, action) => ({
  ...state,
  sidebar: {
    ...state.sidebar,
    activeTab: action.id,
  },
});
