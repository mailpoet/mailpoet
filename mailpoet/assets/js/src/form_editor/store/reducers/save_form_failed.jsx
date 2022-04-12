export const saveFormFailed = (state, action) => {
  const notices = state.notices.filter((notice) => notice.id !== 'save-form');
  notices.push({
    id: 'save-form',
    content: action.message,
    isDismissible: true,
    status: 'error',
  });
  return {
    ...state,
    isFormSaving: false,
    notices,
  };
};
