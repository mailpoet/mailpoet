export default (state, action) => {
  const notices = state.notices.filter(
    (notice) => notice.id !== 'custom-field',
  );
  notices.push({
    id: 'custom-field',
    content: action.message,
    isDismissible: true,
    status: 'error',
  });
  return {
    ...state,
    isCustomFieldSaving: false,
    notices,
  };
};
