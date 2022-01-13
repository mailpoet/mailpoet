export default (state, action) => ({
  ...state,
  sidebar: {
    ...state.sidebar,
    activeTab: action.id,
  },
});
