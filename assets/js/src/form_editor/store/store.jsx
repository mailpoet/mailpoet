/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import * as actions from './actions.jsx';
import createReducer from './reducer.jsx';
import selectors from './selectors.jsx';
import controls from './controls.jsx';

const defaultState = {
  sidebarOpened: true,
  formData: window.mailpoet_form_data,
  formExports: window.mailpoet_form_exports,
  segments: window.mailpoet_form_segments,
  pages: window.mailpoet_form_pages,
  isFormSaving: false,
  notices: [],
};

const config = {
  reducer: createReducer(defaultState),
  actions,
  selectors,
  controls,
  resolvers: {},
};

export default () => (registerStore('mailpoet-form-editor', config));
