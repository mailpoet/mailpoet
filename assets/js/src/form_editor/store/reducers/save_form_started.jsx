import MailPoet from 'mailpoet';

export default (state) => {
  // remove all form saving related notices
  const notices = state.notices.filter((notice) => !['save-form', 'missing-lists'].includes(notice.id));
  const hasMissingLists = state.formErrors.includes('missing-lists');
  if (hasMissingLists) {
    notices.push({
      id: 'missing-lists',
      content: MailPoet.I18n.t('settingsPleaseSelectList'),
      isDismissible: true,
      status: 'error',
    });
  }
  return {
    ...state,
    isFormSaving: !hasMissingLists,
    notices,
  };
};
