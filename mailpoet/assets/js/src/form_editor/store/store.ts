import '@wordpress/notices';
/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import { SETTINGS_DEFAULTS } from '@wordpress/block-editor';
import * as actions from './actions';
import { createReducer } from './reducer.jsx';
import { selectors } from './selectors';
import { controls } from './controls.jsx';
import { validateForm } from './form_validator.jsx';
import { formBodyToBlocksFactory } from './form_body_to_blocks.jsx';
import { mapFormDataAfterLoading } from './map_form_data_after_loading.jsx';
import { FormEditorWindow, State } from './state_types';
import { OmitFirstArgs } from '../../types';

const storeName = 'mailpoet-form-editor';

declare let window: FormEditorWindow;

declare module '@wordpress/data' {
  function select(key: typeof storeName): OmitFirstArgs<typeof selectors>;
}

export const initStore = () => {
  const customFields = window.mailpoet_custom_fields.map((field) => ({
    ...field,
    params: field.params || {},
  }));

  const formBodyToBlocks = formBodyToBlocksFactory(
    SETTINGS_DEFAULTS.fontSizes,
    SETTINGS_DEFAULTS.colors,
    SETTINGS_DEFAULTS.gradients,
    customFields,
  );

  const formData = { ...window.mailpoet_form_data };
  const formBlocks = formBodyToBlocks(formData.body);
  delete formData.body;
  const dateSettingData = {
    dateTypes: window.mailpoet_date_types,
    dateFormats: window.mailpoet_date_formats,
    months: window.mailpoet_month_names,
  };
  formData.settings.segments = formData.settings.segments
    ? formData.settings.segments
    : [];

  let previewSettings = null;
  // We don't want to try to load saved settings for forms that are brand new
  if (formData.id !== null) {
    try {
      previewSettings = JSON.parse(
        window.localStorage.getItem(
          `mailpoet_form_preview_settings${formData.id}`,
        ),
      );

      // Back compatibility - "below_post" was renamed to "below_posts"
      // but local storage can have the old value, let's normalize it.
      if (previewSettings.formType === 'below_post') {
        previewSettings.formType = 'below_posts';
      }
    } catch (e) {
      // We just keep it null
    }
  }

  let fullscreenStatus;
  try {
    fullscreenStatus = JSON.parse(
      window.localStorage.getItem('mailpoet_form_view_options'),
    );
  } catch (e) {
    fullscreenStatus = false;
  }

  const defaultState = {
    editorHistory: [],
    editorHistoryOffset: 0,
    formBlocks,
    formData: mapFormDataAfterLoading(formData),
    dateSettingData,
    sidebarOpened: true,
    formExports: window.mailpoet_form_exports,
    formErrors: validateForm(formData, formBlocks),
    segments: window.mailpoet_form_segments,
    customFields,
    isFormSaving: false,
    isCustomFieldSaving: false,
    isCustomFieldCreating: false,
    isPreviewShown: false,
    isPreviewReady: false,
    isCustomFieldDeleting: false,
    inserterPanel: null,
    notices: [],
    hasUnsavedChanges: false,
    sidebar: {
      activeSidebar: 'default',
      activeTab: 'form',
      openedPanels: ['basic-settings'],
    },
    previewSettings,
    fullscreenStatus,
    editorUrl: window.location.href,
    formEditorUrl: window.mailpoet_form_edit_url,
    previewPageUrl: window.mailpoet_form_preview_page,
    closeIconsUrl: window.mailpoet_close_icons_url,
    customFonts: window.mailpoet_custom_fonts,
    allWpPosts: window.mailpoet_all_wp_posts,
    allWpPages: window.mailpoet_all_wp_pages,
    allWpCategories: window.mailpoet_all_wp_categories,
    allWpTags: window.mailpoet_all_wp_tags,
    allWooCommerceProducts: window.mailpoet_woocommerce_products,
    allWooCommerceCategories: window.mailpoet_woocommerce_categories,
    allWooCommerceTags: window.mailpoet_woocommerce_tags,
    tutorialSeen: window.mailpoet_tutorial_seen === '1',
    tutorialUrl: window.mailpoet_tutorial_url,
    user: {
      isAdministrator: window.mailpoet_is_administrator,
    },
  };

  const config = {
    reducer: createReducer(defaultState),
    actions,
    selectors,
    controls,
    resolvers: {},
  };

  registerStore<State>('mailpoet-form-editor', config);
};
