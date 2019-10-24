import { registerStore } from '@wordpress/data';
import * as actions from './actions.jsx';
import createReducer from './reducer.jsx';
import selectors from './selectors.jsx';
import controls from './controls.jsx';

const defaultState = {
  sidebarOpened: true,
  formData: window.mailpoet_form_data,
  isFormSaving: false,
};

const config = {
  reducer: createReducer(defaultState),
  actions,
  selectors,
  controls,
  resolvers: {},
};

export default () => (registerStore('mailpoet-form-editor', config));
