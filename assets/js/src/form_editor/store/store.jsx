/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import * as actions from './actions.jsx';
import createReducer from './reducer.jsx';
import selectors from './selectors.jsx';
import controls from './controls.jsx';
import validateForm from './form_validator.jsx';
import { formBodyToBlocks } from './form_body_to_blocks.jsx';
import mapFormDataAfterLoading from './map_form_data_after_loading.jsx';

export default () => {
  const formData = { ...window.mailpoet_form_data };
  const formBlocks = formBodyToBlocks(formData.body, window.mailpoet_custom_fields);
  delete formData.body;
  const dateSettingData = {
    dateTypes: window.mailpoet_date_types,
    dateFormats: window.mailpoet_date_formats,
    months: window.mailpoet_month_names,
  };
  formData.settings.segments = formData.settings.segments ? formData.settings.segments : [];
  const defaultState = {
    formBlocks,
    formData: mapFormDataAfterLoading(formData),
    dateSettingData,
    sidebarOpened: true,
    formExports: window.mailpoet_form_exports,
    formErrors: validateForm(formData, formBlocks),
    segments: window.mailpoet_form_segments,
    pages: window.mailpoet_form_pages,
    customFields: window.mailpoet_custom_fields,
    isFormSaving: false,
    isCustomFieldSaving: false,
    isCustomFieldCreating: false,
    notices: [],
    hasUnsavedChanges: false,
    sidebar: {
      activeTab: 'form',
      openedPanels: ['basic-settings'],
    },
  };

  const config = {
    reducer: createReducer(defaultState),
    actions,
    selectors,
    controls,
    resolvers: {},
  };

  registerStore('mailpoet-form-editor', config);
};
