import MailPoet from 'mailpoet';

export default (state) => {
  const notices = state.notices.filter((notice) => notice.id !== 'save-form');
  notices.push({
    id: 'save-form',
    content: MailPoet.I18n.t('formSaved'),
    isDismissible: true,
    status: 'success',
  });
  return {
    ...state,
    isFormSaving: false,
    hasUnsavedChanges: false,
    notices,
  };
};
