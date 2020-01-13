export default (state) => {
  const notices = state.notices.filter((notice) => notice.id !== 'custom-field');
  return {
    ...state,
    isCustomFieldCreating: true,
    notices,
  };
};
