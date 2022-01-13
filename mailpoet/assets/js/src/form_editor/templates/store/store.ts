/**
 * The store is implemented using @wordpress/data module
 * @see https://developer.wordpress.org/block-editor/packages/packages-data/
 */
import { registerStore } from '@wordpress/data';
import selectors from './selectors';
import createReducer from './reducer';
import * as actions from './actions';
import controls from './controls';

import { TemplateData, StateType, CategoryType } from './types';

interface StoreWindow extends Window {
  mailpoet_templates: TemplateData;
  mailpoet_form_edit_url: string;
}

declare let window: StoreWindow;

export default (): void => {
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

  registerStore('mailpoet-form-editor-templates', config);
};
