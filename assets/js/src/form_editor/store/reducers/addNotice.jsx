export default (state, action) => {
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
