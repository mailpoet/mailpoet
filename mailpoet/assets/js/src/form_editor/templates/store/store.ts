/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { createReduxStore, register } from '@wordpress/data';
import { selectors } from './selectors';
import { createReducer } from './reducer';
import * as actions from './actions';
import * as controls from './controls';

import { TemplateData, StateType, CategoryType } from './types';

interface StoreWindow extends Window {
  mailpoet_templates: TemplateData;
  mailpoet_form_edit_url: string;
}

declare let window: StoreWindow;

const storeName = 'mailpoet-form-editor-templates';

export const createStore = () => {
  const defaultState: StateType = {
    templates: window.mailpoet_templates,
    formEditorUrl: window.mailpoet_form_edit_url,
    selectTemplateFailed: false,
    loading: false,
    activeCategory: CategoryType.Popup,
  };

  const config = {
    selectors,
    actions,
    controls,
    reducer: createReducer(defaultState),
    resolvers: {},
  };

  const store = createReduxStore(storeName, config);
  register(store);
  return store;
};

export const store: ReturnType<typeof createStore> = {
  name: storeName,
  instantiate: (registry) => createStore().instantiate(registry),
};
