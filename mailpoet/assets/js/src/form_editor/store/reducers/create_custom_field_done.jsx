import MailPoet from 'mailpoet';

export default (state, action) => {
  const notices = state.notices.filter(
    (notice) => notice.id !== 'custom-field',
  );
  notices.push({
    id: 'custom-field',
    content: MailPoet.I18n.t('customFieldSaved'),
    isDismissible: true,
    status: 'success',
  });

  const customFields = [...state.customFields];
  customFields.push(action.response);

  return {
    ...state,
    isCustomFieldCreating: false,
    notices,
    customFields,
  };
};
