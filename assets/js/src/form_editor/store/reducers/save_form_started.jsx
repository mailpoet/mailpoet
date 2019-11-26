import MailPoet from 'mailpoet';

export default (state) => {
  // remove all form saving related notices
  const notices = state.notices.filter((notice) => !['save-form', 'missing-lists'].includes(notice.id));
  const hasMissingLists = state.formErrors.includes('missing-lists');
  const sidebarOpenedPanels = [...state.sidebar.openedPanels];
  if (hasMissingLists) {
    notices.push({
      id: 'missing-lists',
      content: MailPoet.I18n.t('settingsPleaseSelectList'),
      isDismissible: true,
      status: 'error',
    });
    if (!sidebarOpenedPanels.includes('basic-settings')) {
      sidebarOpenedPanels.push('basic-settings');
    }
  }

  return {
    ...state,
    isFormSaving: !hasMissingLists,
    sidebar: {
      ...state.sidebar,
      activeTab: hasMissingLists ? 'form' : state.sidebar.activeTab,
      openedPanels: sidebarOpenedPanels,
    },
    notices,
  };
};
