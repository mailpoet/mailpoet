export default {
  toggleSidebar(toggleTo) {
    return {
      type: 'TOGGLE_SIDEBAR',
      toggleTo,
    };
  },
  changeFormName(name) {
    return {
      type: 'CHANGE_FORM_NAME',
      name,
    };
  },
};
