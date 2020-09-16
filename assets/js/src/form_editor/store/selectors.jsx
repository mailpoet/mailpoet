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
  isFormEnabled(state) {
    return state.formData.status === 'enabled';
  },
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
  getIsPreviewReady(state) {
    return state.isPreviewReady;
  },
  getPreviewSettings(state) {
    // Use previously used value
    if (state.previewSettings) {
      return state.previewSettings;
    }
    // Otherwise create one based on settings
    const previewSettings = {
      displayType: 'desktop',
      formType: 'others',
    };
    const settings = state.formData.settings;
    if (settings.formPlacement.belowPosts.enabled) {
      previewSettings.formType = 'below_post';
    }
    if (settings.formPlacement.popup.enabled) {
      previewSettings.formType = 'popup';
    }
    if (settings.formPlacement.fixedBar.enabled) {
      previewSettings.formType = 'fixed_bar';
    }
    if (settings.formPlacement.slideIn.enabled) {
      previewSettings.formType = 'slide_in';
    }
    return previewSettings;
  },
  getFormWidth(state, displayType) {
    const settings = state.formData.settings;
    switch (displayType) {
      case 'below_post': return settings.formPlacement.belowPosts.styles.width;
      case 'popup': return settings.formPlacement.popup.styles.width;
      case 'slide_in': return settings.formPlacement.slideIn.styles.width;
      case 'fixed_bar': return settings.formPlacement.fixedBar.styles.width;
      case 'others': return settings.formPlacement.others.styles.width;
      default: throw Error(`Invalid form display type ${displayType}`);
    }
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
  getDefaultSidebarActiveTab(state) {
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
  getEditorUrl(state) {
    return state.editorUrl;
  },
  getPreviewPageUrl(state) {
    return state.previewPageUrl;
  },
  getCloseIconsUrl(state) {
    return state.closeIconsUrl;
  },
  getAllCustomFonts(state) {
    return state.customFonts;
  },
  getActiveSidebar(state) {
    return state.sidebar.activeSidebar;
  },
  getAllWPPosts(state) {
    return state.allWpPosts;
  },
  getAllWPPages(state) {
    return state.allWpPages;
  },
  getAllWPCategories(state) {
    return state.allWpCategories;
  },
  getAllWPTags(state) {
    return state.allWpTags;
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
