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
import formBodyToBlocks from './form_body_to_blocks.jsx';

export default () => {
  const formData = { ...window.mailpoet_form_data };
  const formBlocks = formBodyToBlocks(formData.body);
  delete formData.body;
  formData.settings.segments = formData.settings.segments ? formData.settings.segments : [];
  const defaultState = {
    formBlocks,
    formData,
    sidebarOpened: true,
    formExports: window.mailpoet_form_exports,
    formErrors: validateForm(formData, formBlocks),
    segments: window.mailpoet_form_segments,
    pages: window.mailpoet_form_pages,
    customFields: window.mailpoet_custom_fields,
    isFormSaving: false,
    notices: [],
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

  // Workaround for @wordpress/block-editor dependency on @wordpress/editor's store
  // Block editor store use is triggering an experimental action from core/editor
  // This was already fixed in Gutenberg but it is not released yet
  // https://github.com/WordPress/gutenberg/commit/c97ccf0bea983cc1042cea7a1171a5a7c0ff5770
  const dummyEditorStoreConfig = {
    reducer: (state) => (state),
    actions: {
      __experimentalFetchReusableBlocks: () => ({
        type: 'DUMMY_EXPERIMENTAL',
      }),
    },
    controls: {},
    resolvers: {},
  };
  registerStore('core/editor', dummyEditorStoreConfig);
};
