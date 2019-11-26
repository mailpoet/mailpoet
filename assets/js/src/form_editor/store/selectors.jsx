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
  getAllAvailableSegments(state) {
    return state.segments;
  },
  getAllAvailablePages(state) {
    return state.pages;
  },
  getIsFormSaving(state) {
    return state.isFormSaving;
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
};
