import { State } from './state-types';

const findBlockPath = (blocks, id, path = []) =>
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
  }, []);

export const selectors = {
  isFormSaved(state: State): boolean {
    return typeof state.formData.id === 'number';
  },
  isFormEnabled(state: State): boolean {
    return state.formData.status === 'enabled';
  },
  isFullscreenEnabled(state: State) {
    return state.fullscreenStatus;
  },
  isInserterOpened(state: State) {
    return !!state.inserterPanel;
  },
  getInserterPanelInsertPoint(state: State) {
    return state.inserterPanel;
  },
  getSidebarOpened(state: State) {
    return state.sidebarOpened;
  },
  getFormName(state: State) {
    return state.formData.name;
  },
  getFormData(state: State) {
    return state.formData;
  },
  getFormStyles(state: State) {
    return state.formData.styles;
  },
  getFormExports(state: State) {
    return Object.fromEntries(
      Object.entries(state.formExports).map(([k, v]) => [
        k,
        v.replace(':form_id:', `${state.formData.id}`),
      ]),
    );
  },
  getFormSettings(state: State) {
    return state.formData.settings;
  },
  getAllAvailableSegments(state: State) {
    return state.segments;
  },
  getAllAvailableCustomFields(state: State) {
    return state.customFields;
  },
  getIsFormSaving(state: State) {
    return state.isFormSaving;
  },
  getIsPreviewShown(state: State) {
    return state.isPreviewShown;
  },
  getIsPreviewReady(state: State) {
    return state.isPreviewReady;
  },
  getPreviewSettings(state: State) {
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
      previewSettings.formType = 'below_posts';
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
  getFormWidth(state: State, formType: State['previewSettings']['formType']) {
    const settings = state.formData.settings;
    switch (formType) {
      case 'below_posts':
        return settings.formPlacement.belowPosts.styles.width;
      case 'popup':
        return settings.formPlacement.popup.styles.width;
      case 'slide_in':
        return settings.formPlacement.slideIn.styles.width;
      case 'fixed_bar':
        return settings.formPlacement.fixedBar.styles.width;
      case 'others':
        return settings.formPlacement.others.styles.width;
      default:
        throw Error(`Invalid form display type ${formType as string}`);
    }
  },
  getIsCustomFieldSaving(state: State) {
    return state.isCustomFieldSaving;
  },
  getIsCustomFieldDeleting(state: State) {
    return state.isCustomFieldDeleting;
  },
  getDismissibleNotices(state: State) {
    return state.notices.filter((notice) => notice.isDismissible === true);
  },
  getNonDismissibleNotices(state: State) {
    return state.notices.filter((notice) => notice.isDismissible === false);
  },
  getNotice(state: State, id) {
    return state.notices.find((notice) => notice.id === id);
  },
  getFormErrors(state: State) {
    return state.formErrors;
  },
  getDefaultSidebarActiveTab(state: State) {
    return state.sidebar.activeTab;
  },
  getSidebarOpenedPanels(state: State) {
    return state.sidebar.openedPanels;
  },
  // Initially filled at start of the app and then updated from block editor's onChange callback
  // This is needed as a previous state for validation and auto fix functionality when we re-insert the required blocks (email, submit inputs).
  getFormBlocks(state: State) {
    return state.formBlocks;
  },
  getDateSettingsData(state: State) {
    return state.dateSettingData;
  },
  getIsCustomFieldCreating(state: State) {
    return state.isCustomFieldCreating;
  },
  hasUnsavedChanges(state: State) {
    return state.hasUnsavedChanges;
  },
  getEditorUrl(state: State) {
    return state.editorUrl;
  },
  getPreviewPageUrl(state: State) {
    return state.previewPageUrl;
  },
  getCloseIconsUrl(state: State) {
    return state.closeIconsUrl;
  },
  getAllCustomFonts(state: State) {
    return state.customFonts;
  },
  getActiveSidebar(state: State) {
    return state.sidebar.activeSidebar;
  },
  getAllWPPosts(state: State) {
    return state.allWpPosts;
  },
  getAllWPPages(state: State) {
    return state.allWpPages;
  },
  getAllWPCategories(state: State) {
    return state.allWpCategories;
  },
  getAllWPTags(state: State) {
    return state.allWpTags;
  },
  getAllWooCommerceProducts(state: State) {
    return state.allWooCommerceProducts;
  },
  getAllWooCommerceCategories(state: State) {
    return state.allWooCommerceCategories;
  },
  getAllWooCommerceTags(state: State) {
    return state.allWooCommerceTags;
  },
  getTutorialSeen(state: State) {
    return state.tutorialSeen;
  },
  getFormEditorUrl(state: State) {
    return state.formEditorUrl;
  },
  getTutorialUrl(state: State) {
    return state.tutorialUrl;
  },
  /**
   * Goes thru all parents of the block and return
   * the attribute value from the closest parent which has the attribute defined
   */
  getClosestParentAttribute(state: State, blockId, attributeName) {
    const blockPath = findBlockPath(state.formBlocks, blockId);
    return blockPath.reduce((result, block) => {
      if (block.attributes && block.attributes[attributeName] !== undefined) {
        return block.attributes[attributeName];
      }
      return result;
    }, null);
  },
  hasEditorUndo(state: State) {
    let length = state.editorHistory.length;
    // We add a record with the current state at the end of the history on click,
    // then we have to decrease the length by this record for correct behavior
    if (state.editorHistory.length > 1) {
      length -= 1;
    }

    return length > 0 && length > state.editorHistoryOffset;
  },
  hasEditorRedo(state: State) {
    return state.editorHistoryOffset > 0;
  },
  isUserAdministrator(state: State) {
    return state.user.isAdministrator;
  },
} as const;
