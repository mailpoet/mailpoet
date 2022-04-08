import MailPoet from 'mailpoet';

export default (state, action) => {
  const notices = state.notices.filter((notice) => notice.id !== 'save-form');
  notices.push({
    id: 'save-form',
    content: `${MailPoet.I18n.t('formSaved')} ${MailPoet.I18n.t(
      'formSavedAppendix',
    )}`,
    isDismissible: true,
    status: 'success',
  });
  return {
    ...state,
    formData: {
      ...state.formData,
      id: parseInt(action.formId, 10),
    },
    isFormSaving: false,
    hasUnsavedChanges: false,
    notices,
  };
};
