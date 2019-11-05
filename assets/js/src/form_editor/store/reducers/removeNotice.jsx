export default (state, action) => ({
  ...state,
  notices: [...state.notices].filter((item) => item.id !== action.id),
});
