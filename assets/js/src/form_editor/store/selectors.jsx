export default {
  getSidebarOpened(state) {
    return state.sidebarOpened;
  },
  getFormName(state) {
    return state.formData.name;
  },
  getFormData(state) {
    return state.formData;
  },
  getFormStyles(state) {
    return state.formData.styles;
  },
  getFormExports(state) {
    return state.formExports;
  },
  getFormSettings(state) {
    return state.formData.settings;
  },
  placeFormBellowAllPages(state) {
    return state.formData.settings.placeFormBellowAllPages || false;
  },
  placeFormBellowAllPosts(state) {
    return state.formData.settings.placeFormBellowAllPosts || false;
  },
  getAllAvailableSegments(state) {
    return state.segments;
  },
  getAllAvailableCustomFields(state) {
    return state.customFields;
  },
  getAllAvailablePages(state) {
    return state.pages;
  },
  getIsFormSaving(state) {
    return state.isFormSaving;
  },
  getIsPreviewShown(state) {
    return false;
  },
  getIsCustomFieldSaving(state) {
    return state.isCustomFieldSaving;
  },
  getIsCustomFieldDeleting(state) {
    return state.isCustomFieldDeleting;
  },
  getDismissibleNotices(state) {
    return state.notices.filter((notice) => notice.isDismissible === true);
  },
  getNonDismissibleNotices(state) {
    return state.notices.filter((notice) => notice.isDismissible === false);
  },
  getNotice(state, id) {
    return state.notices.find((notice) => notice.id === id);
  },
  getFormErrors(state) {
    return state.formErrors;
  },
  getSidebarActiveTab(state) {
    return state.sidebar.activeTab;
  },
  getSidebarOpenedPanels(state) {
    return state.sidebar.openedPanels;
  },
  getFormBlocks(state) {
    return state.formBlocks;
  },
  getDateSettingsData(state) {
    return state.dateSettingData;
  },
  getIsCustomFieldCreating(state) {
    return state.isCustomFieldCreating;
  },
  hasUnsavedChanges(state) {
    return state.hasUnsavedChanges;
  },
};
