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
  getIsFormSaving(state) {
    return state.isFormSaving;
  },
  getNotices(state, dismissible = false) {
    return state.notices.filter((notice) => notice.isDismissible === dismissible);
  },
};
