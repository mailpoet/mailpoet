const findBlockPath = (blocks, id, path = []) => (
  blocks.reduce((result, block) => {
    if (result.length) {
      return result;
    }
    if (Array.isArray(block.innerBlocks) && block.innerBlocks.length) {
      path.push(block);
      const child = block.innerBlocks.find((item) => item.clientId === id);
      if (child) {
        return path;
      }
      return findBlockPath(block.innerBlocks, id, path);
    }
    return [];
  }, [])
);

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
  placePopupFormOnAllPages(state) {
    return state.formData.settings.placePopupFormOnAllPages || false;
  },
  placePopupFormOnAllPosts(state) {
    return state.formData.settings.placePopupFormOnAllPosts || false;
  },
  getPopupFormDelay(state) {
    return state.formData.settings.popupFormDelay;
  },
  placeFixedBarFormOnAllPages(state) {
    return state.formData.settings.placeFixedBarFormOnAllPages || false;
  },
  placeFixedBarFormOnAllPosts(state) {
    return state.formData.settings.placeFixedBarFormOnAllPosts || false;
  },
  getFixedBarFormDelay(state) {
    return state.formData.settings.fixedBarFormDelay;
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
    return state.isPreviewShown;
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

  /**
   * Goes thru all parents of the block and return
   * the attribute value from the closest parent which has the attribute defined
   */
  getClosestParentAttribute(state, blockId, attributeName) {
    const blockPath = findBlockPath(state.formBlocks, blockId);
    return blockPath.reduce((result, block) => {
      if (block.attributes && block.attributes[attributeName] !== undefined) {
        return block.attributes[attributeName];
      }
      return result;
    }, null);
  },
};
